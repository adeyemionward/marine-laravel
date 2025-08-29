<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\EquipmentListingResource;
use App\Models\EquipmentListing;
use App\Models\UserFavorite;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function profile(): JsonResponse
    {
        try {
            $user = Auth::user()->load('profile');
            
            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $profile = $user->profile;

            $validated = $request->validate([
                'full_name' => 'sometimes|string|max:255',
                'company_name' => 'sometimes|string|max:255',
                'company_description' => 'sometimes|string|max:1000',
                'phone' => 'sometimes|string|max:20',
                'address' => 'sometimes|string|max:500',
                'city' => 'sometimes|string|max:100',
                'state' => 'sometimes|string|max:100',
                'country' => 'sometimes|string|max:100',
            ]);

            if ($profile) {
                $profile->update($validated);
            } else {
                $validated['user_id'] = $user->id;
                $profile = $user->profile()->create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new UserResource($user->fresh('profile')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listings(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;
            
            $listings = EquipmentListing::where('seller_id', $userId)
                ->with(['category', 'seller'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => EquipmentListingResource::collection($listings),
                'meta' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function favorites(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;
            
            $favorites = UserFavorite::where('user_profile_id', $userId)
                ->with(['listing.category', 'listing.seller'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $listings = $favorites->getCollection()->map(function ($favorite) {
                return $favorite->listing;
            });

            return response()->json([
                'success' => true,
                'data' => EquipmentListingResource::collection($listings),
                'meta' => [
                    'current_page' => $favorites->currentPage(),
                    'per_page' => $favorites->perPage(),
                    'total' => $favorites->total(),
                    'last_page' => $favorites->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch favorites',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function subscription(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;
            
            $subscription = Subscription::where('user_id', $userId)
                ->with('plan')
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No active subscription found',
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'started_at' => $subscription->started_at,
                    'expires_at' => $subscription->expires_at,
                    'auto_renew' => $subscription->auto_renew,
                    'plan' => [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                        'tier' => $subscription->plan->tier,
                        'price' => $subscription->plan->price,
                        'billing_cycle' => $subscription->plan->billing_cycle,
                        'features' => $subscription->plan->features,
                        'max_listings' => $subscription->plan->max_listings,
                    ],
                    'days_remaining' => $subscription->expires_at ? 
                        max(0, $subscription->expires_at->diffInDays(now())) : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}