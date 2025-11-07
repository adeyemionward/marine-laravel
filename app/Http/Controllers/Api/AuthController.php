<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Mail\NewUserAdminNotification;
use App\Mail\NewUserNotification;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => null, // Will be verified via email
            ]);

            // Assign default 'user' role using Spatie
          //  $user->assignRole('user');

            // Create user profile
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
                'role' => 'user', // Keep this for backward compatibility
                'is_active' => true,
                'company_name' => $request->company_name ?? null,
                'phone' => $request->phone ?? null,
                'city' => $request->city ?? null,
                'state' => $request->state ?? null,
                'country' => $request->country ?? 'Nigeria',
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            // Send email verification notification
            $user->notify(new \App\Notifications\EmailVerificationNotification());

            // Send welcome email
            Mail::to($user->email)->send(new NewUserNotification($user));

            // Send notification to admin
            Mail::to('info@marine.ng')->send(new NewUserAdminNotification($user));


            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => new UserResource($user->load(['profile', 'roles', 'sellerProfile'])),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials',
                ], 401);
            }

            $user = Auth::user();

            // Check if user is active
            if (!$user->profile || !$user->profile->is_active) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.',
                ], 403);
            }



            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => new UserResource($user->load(['profile', 'roles', 'sellerProfile'])),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user
            $user = $request->user();

            // Check if user is authenticated
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'Already logged out',
                ]);
            }

            // Get current token
            $currentToken = $user->currentAccessToken();

            // Delete current token if it exists
            if ($currentToken) {
                $currentToken->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Logout error: ' . $e->getMessage());

            // Still return success since the client should clear local data anyway
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ]);
        }
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['profile', 'roles', 'sellerProfile']);

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'user' => new UserResource($user->load(['profile', 'roles', 'sellerProfile'])),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email:rfc,dns'
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link sent to your email',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unable to send password reset link',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password reset successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired password reset token',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'hash' => 'required|string',
        ]);

        try {
            $user = User::findOrFail($request->id);

            if (!hash_equals(
                (string) $request->hash,
                sha1($user->getEmailForVerification())
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification link',
                ], 400);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email already verified',
                ]);
            }

            $user->markEmailAsVerified();

            // Update profile email verification
            if ($user->profile) {
                $user->profile->update([
                    'email_verified_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend email verification notification
     */
    public function resendVerification(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified',
                ], 400);
            }

            $user->notify(new \App\Notifications\EmailVerificationNotification());

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
