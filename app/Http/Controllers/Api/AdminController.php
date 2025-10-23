<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentListing;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\SystemSetting;
use App\Models\SellerApplication;
use App\Models\SellerProfile;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\BannerPurchaseRequest;
use App\Models\FinancialTransaction;
use App\Http\Resources\EquipmentListingResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AdminController extends Controller
{
    public function listings(): JsonResponse
    {
        try {
            $listings = EquipmentListing::with(['category', 'seller'])
                ->when(request('status'), function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when(request('search'), function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('brand', 'like', "%{$search}%");
                    });
                })
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
                'message' => 'Failed to fetch listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function approveListing($id): JsonResponse
    {
        try {
            $listing = EquipmentListing::with(['category', 'seller'])->findOrFail($id);

            $listing->update([
                'status' => 'active',
                'is_verified' => true,
                'published_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Listing approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectListing(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $listing = EquipmentListing::with(['category', 'seller'])->findOrFail($id);

            $listing->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['reason'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Listing rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function featureListing($id): JsonResponse
    {
        try {
            $listing = EquipmentListing::with(['category', 'seller'])->findOrFail($id);

            $listing->update([
                'is_featured' => !$listing->is_featured,
            ]);

            // TODO: Add featured listing payment processing here
            // When featuring a listing with payment:
            // FinancialTransaction::recordFeaturedListingPayment($listing, $amount, $paymentMethod, $paymentReference);

            $message = $listing->is_featured ? 'Listing featured successfully' : 'Listing unfeatured successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['is_featured' => $listing->is_featured],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing feature status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function users(): JsonResponse
    {
        try {
            // Handle includes parameter - only include relationships that actually exist
            $includes = ['profile']; // Default includes
            if (request('include')) {
                $requestedIncludes = explode(',', request('include'));
                // Only allow relationships that actually exist on the User model
                $allowedIncludes = ['profile', 'subscription', 'subscriptions'];
                $validIncludes = array_intersect($requestedIncludes, $allowedIncludes);

                // Map subscription to subscriptions for compatibility
                $includes = ['profile']; // Always include profile
                foreach ($validIncludes as $include) {
                    if ($include === 'subscription') {
                        $includes[] = 'subscriptions.plan'; // Map to actual relationship name with plan
                    } elseif ($include === 'subscriptions') {
                        $includes[] = 'subscriptions.plan';
                    }
                    // Skip invoices as it doesn't exist on User model
                }
            }

            $users = User::with($includes)
                ->when(request('role'), function ($query, $role) {
                    $query->whereHas('profile', function ($q) use ($role) {
                        $q->where('role', $role);
                    });
                })
                ->when(request('search'), function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhereHas('profile', function ($pq) use ($search) {
                              $pq->where('full_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                          });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUser($id, Request $request): JsonResponse
    {
        try {
            // Handle includes parameter
            $includes = ['profile']; // Default includes
            if ($request->get('include')) {
                $requestedIncludes = explode(',', $request->get('include'));
                $allowedIncludes = ['profile', 'subscription', 'subscriptions'];
                $validIncludes = array_intersect($requestedIncludes, $allowedIncludes);

                $includes = ['profile']; // Always include profile
                foreach ($validIncludes as $include) {
                    if ($include === 'subscription') {
                        $includes[] = 'subscriptions'; // Map to actual relationship name
                    } elseif ($include === 'subscriptions') {
                        $includes[] = 'subscriptions';
                    }
                }
            }

            $user = User::with($includes)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyUser($id): JsonResponse
    {
        try {
            $userProfile = UserProfile::findOrFail($id);

            $userProfile->update([
                'is_verified' => !$userProfile->is_verified,
                'email_verified_at' => $userProfile->is_verified ? null : now(),
            ]);

            $message = $userProfile->is_verified ? 'User verified successfully' : 'User verification removed';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['is_verified' => $userProfile->is_verified],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user verification status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function banUser(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:500',
                'duration_days' => 'nullable|integer|min:1|max:365',
            ]);

            $userProfile = UserProfile::findOrFail($id);

            $userProfile->update([
                'is_active' => !$userProfile->is_active,
                'ban_reason' => !$userProfile->is_active ? ($validated['reason'] ?? 'Banned by admin') : null,
                'banned_until' => !$userProfile->is_active && isset($validated['duration_days']) ?
                    now()->addDays($validated['duration_days']) : null,
            ]);

            $message = !$userProfile->is_active ? 'User banned successfully' : 'User unbanned successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['is_active' => $userProfile->is_active],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user ban status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dashboardAnalytics(): JsonResponse
    {
        try {
            $now = Carbon::now();
            $lastMonth = $now->copy()->subMonth();

            $analytics = [
                'overview' => [
                    'total_listings' => EquipmentListing::count(),
                    'active_listings' => EquipmentListing::where('status', 'active')->count(),
                    'total_users' => User::count(),
                    'verified_users' => UserProfile::where('is_verified', true)->count(),
                ],
                'recent' => [
                    'new_listings_today' => EquipmentListing::whereDate('created_at', $now->toDateString())->count(),
                    'new_users_today' => User::whereDate('created_at', $now->toDateString())->count(),
                    'new_listings_this_week' => EquipmentListing::where('created_at', '>=', $now->startOfWeek())->count(),
                    'new_users_this_week' => User::where('created_at', '>=', $now->startOfWeek())->count(),
                ],
                'status_breakdown' => [
                    'active' => EquipmentListing::where('status', 'active')->count(),
                    'draft' => EquipmentListing::where('status', 'draft')->count(),
                    'pending' => EquipmentListing::where('status', 'pending')->count(),
                    'sold' => EquipmentListing::where('status', 'sold')->count(),
                    'archived' => EquipmentListing::where('status', 'archived')->count(),
                ],
                'growth' => [
                    'listings_growth' => [
                        'current_month' => EquipmentListing::where('created_at', '>=', $now->startOfMonth())->count(),
                        'last_month' => EquipmentListing::whereBetween('created_at', [
                            $lastMonth->startOfMonth(),
                            $lastMonth->endOfMonth()
                        ])->count(),
                    ],
                    'users_growth' => [
                        'current_month' => User::where('created_at', '>=', $now->startOfMonth())->count(),
                        'last_month' => User::whereBetween('created_at', [
                            $lastMonth->startOfMonth(),
                            $lastMonth->endOfMonth()
                        ])->count(),
                    ],
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unbanUser($id): JsonResponse
    {
        try {
            $userProfile = UserProfile::findOrFail($id);

            $userProfile->update([
                'is_active' => true,
                'ban_reason' => null,
                'banned_until' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User unbanned successfully',
                'data' => ['is_active' => $userProfile->is_active],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unban user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteUser($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting admin users
            if ($user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete admin users',
                ], 403);
            }

            // Soft delete or hard delete based on business logic
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateUserStatus(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:active,inactive,banned,suspended',
                'reason' => 'nullable|string|max:500',
            ]);

            $userProfile = UserProfile::findOrFail($id);

            $updates = [];

            switch ($validated['status']) {
                case 'active':
                    $updates = [
                        'is_active' => true,
                        'ban_reason' => null,
                        'banned_until' => null,
                    ];
                    break;
                case 'inactive':
                    $updates = [
                        'is_active' => false,
                        'ban_reason' => $validated['reason'] ?? 'Account deactivated by admin',
                    ];
                    break;
                case 'banned':
                case 'suspended':
                    $updates = [
                        'is_active' => false,
                        'ban_reason' => $validated['reason'] ?? 'Banned by admin',
                    ];
                    break;
            }

            $userProfile->update($updates);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => $userProfile->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateUserRole(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|in:user,seller,admin,moderator',
            ]);

            $userProfile = UserProfile::findOrFail($id);

            // Prevent demoting the last admin
            if ($userProfile->role === 'admin' && $validated['role'] !== 'admin') {
                $adminCount = UserProfile::where('role', 'admin')->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot demote the last admin user',
                    ], 403);
                }
            }

            $userProfile->update(['role' => $validated['role']]);

            return response()->json([
                'success' => true,
                'message' => 'User role updated successfully',
                'data' => $userProfile->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function promoteToSeller(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_name' => 'required|string|max:255',
                'business_description' => 'nullable|string|max:1000',
                'specialties' => 'nullable|array',
                'years_experience' => 'nullable|integer|min:0',
                'verification_level' => 'nullable|string|in:basic,premium,enterprise',
                'admin_notes' => 'nullable|string|max:500',
            ]);

            $user = User::with('profile')->findOrFail($id);

            // Check if user is already a seller
            if ($user->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already a seller',
                ], 400);
            }

            // Create seller profile
            $sellerProfile = SellerProfile::create([
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'business_description' => $validated['business_description'] ?? null,
                'specialties' => $validated['specialties'] ?? [],
                'years_active' => $validated['years_experience'] ?? 0,
                'verification_status' => 'approved',
                'verified_at' => now(),
                'verification_level' => $validated['verification_level'] ?? 'basic',
            ]);

            // Update user role to seller
            $user->update([
                'seller_profile_id' => $sellerProfile->id,
            ]);

            // Update user profile
            $user->profile->update([
                'role' => 'seller',
                'is_verified' => true,
                'email_verified_at' => $user->profile->email_verified_at ?? now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User successfully promoted to seller',
                'data' => [
                    'user' => new UserResource($user->fresh(['profile'])),
                    'seller_profile' => $sellerProfile,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote user to seller',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchUsers(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');

            if (strlen($query) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query must be at least 2 characters',
                ], 400);
            }

            $users = User::with('profile')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%")
                      ->orWhereHas('profile', function ($pq) use ($query) {
                          $pq->where('full_name', 'like', "%{$query}%")
                            ->orWhere('company_name', 'like', "%{$query}%");
                      });
                })
                ->when($request->get('role'), function ($q, $role) {
                    $q->whereHas('profile', function ($pq) use ($role) {
                        $pq->where('role', $role);
                    });
                })
                ->when($request->get('status'), function ($q, $status) {
                    if ($status === 'active') {
                        $q->whereHas('profile', function ($pq) {
                            $pq->where('is_active', true);
                        });
                    } else if ($status === 'inactive' || $status === 'banned') {
                        $q->whereHas('profile', function ($pq) {
                            $pq->where('is_active', false);
                        });
                    }
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function userAnalytics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::whereHas('profile', function ($q) {
                    $q->where('is_active', true);
                })->count(),
                'verified_users' => User::whereHas('profile', function ($q) {
                    $q->where('is_verified', true);
                })->count(),
                'banned_users' => User::whereHas('profile', function ($q) {
                    $q->where('is_active', false);
                })->count(),
                'users_by_role' => User::join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                    ->selectRaw('user_profiles.role, count(*) as count')
                    ->groupBy('user_profiles.role')
                    ->pluck('count', 'role'),
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
                'users_registered_this_month' => User::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function settings(): JsonResponse
    {
        try {
            $settings = SystemSetting::all()->pluck('value', 'key');

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $settings = $request->all();

            foreach ($settings as $key => $value) {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function publicSettings(): JsonResponse
    {
        try {
            $publicKeys = [
                'site_name',
                'site_description',
                'contact_email',
                'contact_phone',
                'social_facebook',
                'social_twitter',
                'social_instagram',
                'maintenance_mode',
            ];

            $settings = SystemSetting::whereIn('key', $publicKeys)
                ->pluck('value', 'key');

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch public settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Seller Application Management Methods

    public function getSellerApplications(): JsonResponse
    {
        try {
            $applications = SellerApplication::with(['user', 'reviewer'])
                ->when(request('status'), function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when(request('search'), function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('business_name', 'like', "%{$search}%")
                          ->orWhereHas('user', function ($uq) use ($search) {
                              $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                          });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $applications->items(),
                'meta' => [
                    'current_page' => $applications->currentPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                    'last_page' => $applications->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch seller applications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSellerApplicationStats(): JsonResponse
    {
        try {
            $stats = [
                'total' => SellerApplication::count(),
                'pending' => SellerApplication::where('status', 'pending')->count(),
                'under_review' => SellerApplication::where('status', 'under_review')->count(),
                'approved' => SellerApplication::where('status', 'approved')->count(),
                'rejected' => SellerApplication::where('status', 'rejected')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch application stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSellerApplication($id): JsonResponse
    {
        try {
            $application = SellerApplication::with(['user', 'reviewer'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $application,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function approveSellerApplication(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|exists:seller_applications,id',
                'plan_id' => 'nullable|integer|exists:subscription_plans,id',
                'admin_notes' => 'nullable|string',
                'verification_level' => 'nullable|string|in:basic,premium,enterprise',
                'welcome_message' => 'nullable|boolean',
                'send_email' => 'nullable|boolean',
                'grant_immediate_access' => 'nullable|boolean',
                'auto_generate_invoice' => 'nullable|boolean',
                'skip_invoice' => 'nullable|boolean',
            ]);

            $application = SellerApplication::findOrFail($validated['application_id']);
            $application->approve(auth()->user(), $validated['admin_notes'] ?? null);

            $response = [
                'success' => true,
                'message' => 'Seller application approved successfully',
                'data' => [
                    'application' => $application->fresh(['user', 'reviewer']),
                ]
            ];

            // Check if we should generate invoice automatically
            $autoGenerateInvoice = $validated['auto_generate_invoice'] ?? true; // Default to true
            $skipInvoice = $validated['skip_invoice'] ?? false;
            $grantImmediateAccess = $validated['grant_immediate_access'] ?? false;
            $planId = $validated['plan_id'] ?? null;

            if ($grantImmediateAccess || $skipInvoice) {
                // Grant immediate access without invoice
                $this->grantImmediateSellerAccess($application, $planId);
                $response['message'] = 'Seller application approved with immediate access granted';
                $response['data']['immediate_access'] = true;
            } elseif ($autoGenerateInvoice) {
                // Generate invoice automatically (default behavior)
                $invoiceWorkflowService = app(\App\Services\InvoiceWorkflowService::class);
                $invoice = $invoiceWorkflowService->generateSellerApprovalInvoice($application, auth()->user(), $planId);

                $response['message'] = 'Seller application approved and invoice generated successfully';
                $response['data']['invoice'] = [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount' => $invoice->total_amount,
                    'due_date' => $invoice->due_date,
                    'status' => $invoice->status,
                ];
                $response['data']['auto_invoice'] = true;
            } else {
                // Manual invoice generation (admin will create invoice later)
                $response['message'] = 'Seller application approved. Manual invoice generation required.';
                $response['data']['manual_invoice'] = true;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Grant immediate seller access without requiring payment
     */
    private function grantImmediateSellerAccess(SellerApplication $application, $planId = null): void
    {
        $user = $application->user;

        // Get the plan - use provided plan_id or default to basic
        if ($planId) {
            $plan = \App\Models\SubscriptionPlan::find($planId);
        } else {
            $plan = \App\Models\SubscriptionPlan::where('tier', 'basic')
                ->where('is_active', true)
                ->first();
        }

        if (!$plan) {
            \Log::warning('No subscription plan found for immediate access', [
                'plan_id' => $planId,
                'application_id' => $application->id
            ]);
            return;
        }

        // Create active subscription without payment
        $userProfileId = $user->profile ? $user->profile->id : $user->id;

        \App\Models\Subscription::create([
            'user_id' => $userProfileId,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30), // 30 days trial or full access
            'auto_renew' => false, // Don't auto-renew for manually granted access
        ]);

        \Log::info('Subscription created for immediate access', [
            'user_id' => $userProfileId,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name
        ]);

        // Activate seller profile
        if ($user->sellerProfile) {
            $user->sellerProfile->update([
                'verification_status' => 'approved',
                'verified_at' => now(),
            ]);
        }
    }

    /**
     * Manually generate invoice for approved seller application
     */
    public function generateInvoiceForApplication(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|exists:seller_applications,id',
                'plan_id' => 'nullable|exists:subscription_plans,id',
                'custom_amount' => 'nullable|numeric|min:0',
                'discount_amount' => 'nullable|numeric|min:0',
                'due_date' => 'nullable|date|after:today',
                'notes' => 'nullable|string|max:1000',
                'send_email' => 'nullable|boolean',
            ]);

            $application = SellerApplication::with('user')->findOrFail($validated['application_id']);

            // Check if application is approved
            if ($application->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Application must be approved before generating invoice',
                ], 400);
            }

            // Check if invoice already exists for this application
            $existingInvoice = Invoice::where('seller_application_id', $application->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if ($existingInvoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice already exists for this application',
                    'data' => ['existing_invoice_id' => $existingInvoice->id],
                ], 400);
            }

            // Get subscription plan
            $planId = $validated['plan_id'] ?? null;
            $plan = null;

            if ($planId) {
                $plan = SubscriptionPlan::findOrFail($planId);
            } else {
                $plan = SubscriptionPlan::where('tier', 'basic')
                    ->where('is_active', true)
                    ->first();
            }

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subscription plan found',
                ], 400);
            }

            // Calculate amounts
            $baseAmount = $validated['custom_amount'] ?? $plan->price;
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxRate = 7.5; // VAT in Nigeria
            $discountedAmount = $baseAmount - $discountAmount;
            $taxAmount = ($discountedAmount * $taxRate) / 100;
            $totalAmount = $discountedAmount + $taxAmount;

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'user_id' => $application->user_id,
                'seller_application_id' => $application->id,
                'plan_id' => $plan->id,
                'amount' => $baseAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'invoice_type' => 'subscription',
                'tax_rate' => $taxRate,
                'due_date' => $validated['due_date'] ? \Carbon\Carbon::parse($validated['due_date']) : now()->addDays(14),
                'items' => [
                    [
                        'name' => $plan->name . ' Subscription',
                        'description' => 'Monthly seller subscription - ' . $plan->description,
                        'quantity' => 1,
                        'unit_price' => $baseAmount,
                        'discount' => $discountAmount,
                        'total' => $discountedAmount
                    ]
                ],
                'notes' => $validated['notes'] ?? 'Manual invoice generated by admin. Please complete payment to activate your seller account.',
                'terms_and_conditions' => $this->getSellerTermsAndConditions(),
                'company_name' => $application->business_name,
                'generated_by' => auth()->id(),
            ]);

            // Send email if requested
            if ($validated['send_email'] ?? false) {
                // TODO: Send email notification
                \Log::info('Manual invoice email sent', ['invoice_id' => $invoice->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully',
                'data' => [
                    'invoice' => [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'total_amount' => $invoice->total_amount,
                        'due_date' => $invoice->due_date,
                        'status' => $invoice->status,
                    ],
                    'application' => $application,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller terms and conditions
     */
    private function getSellerTermsAndConditions(): string
    {
        return "By paying this invoice, you agree to Marine.ng's seller terms and conditions:\n\n" .
               "1. Monthly subscription fee is required to maintain active seller status\n" .
               "2. Late payments may result in temporary suspension of seller privileges\n" .
               "3. All listings must comply with Marine.ng quality standards\n" .
               "4. Commission fees apply to completed transactions\n" .
               "5. Subscription auto-renews unless cancelled\n\n" .
               "For full terms, visit: https://marine.ng/seller-terms";
    }

    public function rejectSellerApplication(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $application = SellerApplication::findOrFail($id);
            $application->reject(auth()->user(), $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Application rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateApplicationStatus(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:pending,under_review,approved,rejected',
                'notes' => 'nullable|string',
            ]);

            $application = SellerApplication::findOrFail($id);

            $application->update([
                'status' => $validated['status'],
                'admin_notes' => $validated['notes'] ?? $application->admin_notes,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Application status updated successfully',
                'data' => $application->fresh(['user', 'reviewer']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Subscription Plan Management Methods

    public function getSubscriptionPlans(): JsonResponse
    {
        try {
            $plans = SubscriptionPlan::active()
                ->orderBy('sort_order')
                ->orderBy('price', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $plans,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription plans',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSubscriptionPlanStats($planId): JsonResponse
    {
        try {
            $plan = SubscriptionPlan::findOrFail($planId);

            // Count active subscriptions for this plan
            $activeUsersCount = Subscription::where('subscription_plan_id', $planId)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('expires_at', '>', now())
                          ->orWhereNull('expires_at');
                })
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'plan_id' => $planId,
                    'active_users_count' => $activeUsersCount,
                    'plan_name' => $plan->name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription plan stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createSubscriptionPlan(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'tier' => 'required|string|in:freemium,premium,enterprise',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'billing_cycle' => 'required|string|in:monthly,yearly',
                'features' => 'nullable|array',
                'limits' => 'nullable|array',
                'max_listings' => 'nullable|integer|min:-1',
                'max_images_per_listing' => 'nullable|integer|min:1',
                'priority_support' => 'nullable|boolean',
                'analytics_access' => 'nullable|boolean',
                'custom_branding' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer',
            ]);

            $validated['created_by'] = auth()->id();
            $validated['is_active'] = $validated['is_active'] ?? true;

            $plan = SubscriptionPlan::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan created successfully',
                'data' => $plan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSubscriptionPlan(Request $request, $id): JsonResponse
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'tier' => 'sometimes|string|in:freemium,premium,enterprise',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'billing_cycle' => 'sometimes|string|in:monthly,yearly',
                'features' => 'nullable|array',
                'limits' => 'nullable|array',
                'max_listings' => 'nullable|integer|min:-1',
                'max_images_per_listing' => 'nullable|integer|min:1',
                'priority_support' => 'nullable|boolean',
                'analytics_access' => 'nullable|boolean',
                'custom_branding' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer',
            ]);

            $plan->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan updated successfully',
                'data' => $plan->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSubscriptionPlan($id): JsonResponse
    {
        try {
            $plan = SubscriptionPlan::findOrFail($id);

            // Check if plan has active subscriptions
            $hasActiveSubscriptions = $plan->activeSubscriptions()->exists();

            if ($hasActiveSubscriptions) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete plan with active subscriptions. Deactivate it instead.',
                ], 400);
            }

            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subscription plan deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subscription plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Enhanced User Management Methods

    public function createUser(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'nullable|string|in:user,seller,admin,moderator',
                'full_name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'is_active' => 'nullable|boolean',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);

            // Create user profile
            $user->profile()->create([
                'full_name' => $validated['full_name'] ?? $validated['name'],
                'company_name' => $validated['company_name'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'role' => $validated['role'] ?? 'user',
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->load('profile'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateUser(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:users,email,'.$id,
                'full_name' => 'nullable|string|max:255',
                'company_name' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'role' => 'nullable|string|in:user,seller,admin,moderator',
                'is_active' => 'nullable|boolean',
            ]);

            $user = User::with('profile')->findOrFail($id);

            // Update user table
            $userUpdates = array_filter([
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'] ?? null,
            ]);

            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            // Update profile
            $profileUpdates = array_filter([
                'full_name' => $validated['full_name'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'role' => $validated['role'] ?? null,
                'is_active' => $validated['is_active'] ?? null,
            ], fn($value) => $value !== null);

            if (!empty($profileUpdates)) {
                $user->profile()->update($profileUpdates);
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user->fresh(['profile']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserStats(): JsonResponse
    {
        try {
            $totalUsers = User::count();
            $activeUsers = UserProfile::where('is_active', true)->count();
            $inactiveUsers = UserProfile::where('is_active', false)->count();
            $suspendedUsers = UserProfile::whereNotNull('ban_reason')->count();

            // New users this month
            $thisMonth = Carbon::now()->startOfMonth();
            $newUsersThisMonth = User::where('created_at', '>=', $thisMonth)->count();

            // Users by role
            $usersByRole = UserProfile::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();

            $stats = [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'suspended_users' => $suspendedUsers,
                'new_users_this_month' => $newUsersThisMonth,
                'users_by_role' => $usersByRole,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportUsers(Request $request): JsonResponse
    {
        try {
            $query = User::with('profile');

            // Apply filters
            if ($request->has('role') && $request->role !== 'all') {
                $query->whereHas('profile', function ($q) use ($request) {
                    $q->where('role', $request->role);
                });
            }

            if ($request->has('status') && $request->status !== 'all') {
                $isActive = $request->status === 'active';
                $query->whereHas('profile', function ($q) use ($isActive) {
                    $q->where('is_active', $isActive);
                });
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('profile', function ($pq) use ($search) {
                          $pq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                      });
                });
            }

            $users = $query->get();

            // Generate CSV content
            $csvData = [];
            $csvData[] = ['ID', 'Name', 'Email', 'Full Name', 'Company', 'Role', 'Status', 'Created At'];

            foreach ($users as $user) {
                $csvData[] = [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->profile->full_name ?? '',
                    $user->profile->company_name ?? '',
                    $user->profile->role ?? 'user',
                    $user->profile->is_active ? 'Active' : 'Inactive',
                    $user->created_at->format('Y-m-d H:i:s'),
                ];
            }

            $filename = 'users_export_' . date('Y_m_d_H_i_s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->streamDownload(function () use ($csvData) {
                $handle = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, $filename, $headers);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function suspendUser(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
                'duration' => 'nullable|integer|min:1', // days
            ]);

            $userProfile = UserProfile::findOrFail($id);

            $updates = [
                'is_active' => false,
                'ban_reason' => $validated['reason'],
            ];

            if (isset($validated['duration'])) {
                $updates['banned_until'] = Carbon::now()->addDays($validated['duration']);
            }

            $userProfile->update($updates);

            return response()->json([
                'success' => true,
                'message' => 'User suspended successfully',
                'data' => $userProfile->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unsuspendUser($id): JsonResponse
    {
        try {
            $userProfile = UserProfile::findOrFail($id);

            $userProfile->update([
                'is_active' => true,
                'ban_reason' => null,
                'banned_until' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User unsuspended successfully',
                'data' => $userProfile->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unsuspend user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyUserEmail($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $user->update([
                'email_verified_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User email verified successfully',
                'data' => $user->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify user email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserActivity($id): JsonResponse
    {
        try {
            $user = User::with(['profile', 'equipmentListings', 'sellerProfile'])->findOrFail($id);

            $activities = [];

            // Add registration activity
            $activities[] = [
                'type' => 'registration',
                'description' => 'User registered',
                'timestamp' => $user->created_at,
                'data' => null,
            ];

            // Add listing activities
            foreach ($user->equipmentListings()->latest()->take(10)->get() as $listing) {
                $activities[] = [
                    'type' => 'listing_created',
                    'description' => "Created listing: {$listing->title}",
                    'timestamp' => $listing->created_at,
                    'data' => ['listing_id' => $listing->id, 'title' => $listing->title],
                ];
            }

            // Sort by timestamp
            usort($activities, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            return response()->json([
                'success' => true,
                'data' => array_slice($activities, 0, 50), // Limit to 50 activities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user activity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // User Subscription Management Methods

    public function createUserSubscription(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:subscription_plans,id',
            ]);

            $user = User::findOrFail($id);
            $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

            // Check if user already has an active subscription
            $existingSubscription = $user->subscription()
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has an active subscription',
                ], 400);
            }

            // Create subscription
            $subscription = $user->subscription()->create([
                'plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonth(),
                'auto_renew' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'data' => $subscription->load('plan'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function upgradeUserSubscription(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:subscription_plans,id',
            ]);

            $user = User::findOrFail($id);
            $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);

            $subscription = $user->subscription()
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no active subscription to upgrade',
                ], 404);
            }

            // Update subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'updated_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription upgraded successfully',
                'data' => $subscription->fresh(['plan']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancelUserSubscription($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $subscription = $user->subscription()
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no active subscription to cancel',
                ], 404);
            }

            // Use the model's cancel method
            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'data' => $subscription->fresh(['plan']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Invoice Management Methods

    public function createInvoice(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:500',
                'due_date' => 'nullable|date|after:today',
            ]);

            // Generate invoice number
            $invoiceNumber = 'INV-' . strtoupper(uniqid());

            $invoice = [
                'invoice_number' => $invoiceNumber,
                'user_id' => $validated['user_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'status' => 'pending',
                'issued_at' => Carbon::now(),
                'due_date' => $validated['due_date'] ?? Carbon::now()->addDays(30),
                'created_by' => auth()->id(),
            ];

            // You'll need to implement Invoice model
            // $createdInvoice = Invoice::create($invoice);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice, // Replace with $createdInvoice when model is ready
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Additional helper methods

    public function getUserLoginHistory($id): JsonResponse
    {
        try {
            // This would require a login_logs table
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Login history feature not implemented yet',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch login history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendUserMessage(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            // This would integrate with your messaging system
            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resetUserPassword(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'password' => 'required|string|min:8',
            ]);

            $user = User::findOrFail($id);
            $user->update([
                'password' => bcrypt($validated['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserPermissions($id): JsonResponse
    {
        try {
            $user = User::with('profile')->findOrFail($id);

            // Basic permissions based on role
            $permissions = [
                'can_create_listings' => $user->isSeller() || $user->isAdmin(),
                'can_message' => true,
                'can_favorite' => true,
                'is_admin' => $user->isAdmin(),
                'is_seller' => $user->isSeller(),
            ];

            return response()->json([
                'success' => true,
                'data' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user permissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateUserPermissions(Request $request, $id): JsonResponse
    {
        try {
            // This would be implemented when you have a permissions system
            return response()->json([
                'success' => true,
                'message' => 'Permissions system not fully implemented yet',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSystemMetrics(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            // Get user metrics
            $totalUsers = User::count();
            $newUsersToday = User::whereDate('created_at', today())->count();
            $newUsersThisWeek = User::where('created_at', '>=', Carbon::now()->startOfWeek())->count();
            $newUsersThisMonth = User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();

            // Get listing metrics
            $totalListings = EquipmentListing::count();
            $activeListings = EquipmentListing::where('status', 'active')->count();
            $pendingListings = EquipmentListing::where('status', 'pending')->count();
            $newListingsToday = EquipmentListing::whereDate('created_at', today())->count();
            $newListingsThisWeek = EquipmentListing::where('created_at', '>=', Carbon::now()->startOfWeek())->count();

            // Get seller metrics
            $totalSellers = UserProfile::where('role', 'seller')->count();
            $newSellersThisMonth = UserProfile::where('role', 'seller')
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count();

            // Get subscription metrics
            $activeSubscriptions = Subscription::where('status', 'active')->count();

            // Handle missing amount column gracefully
            try {
                $totalRevenue = Subscription::where('status', 'active')->sum('amount');
            } catch (\Exception $e) {
                // Fallback if amount column doesn't exist
                $totalRevenue = 0;
            }

            // Generate time series data for charts
            $userGrowthData = [];
            $listingGrowthData = [];
            $revenueData = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dateString = $date->format('Y-m-d');

                // User growth
                $usersOnDate = User::whereDate('created_at', '<=', $date)->count();
                $userGrowthData[] = [
                    'date' => $dateString,
                    'value' => $usersOnDate,
                    'name' => $date->format('M d')
                ];

                // Listing growth
                $listingsOnDate = EquipmentListing::whereDate('created_at', '<=', $date)->count();
                $listingGrowthData[] = [
                    'date' => $dateString,
                    'value' => $listingsOnDate,
                    'name' => $date->format('M d')
                ];

                // Revenue data (simulated for now)
                try {
                    $revenueOnDate = Subscription::where('created_at', '<=', $date)
                        ->where('status', 'active')
                        ->sum('amount');
                } catch (\Exception $e) {
                    $revenueOnDate = 0;
                }
                $revenueData[] = [
                    'date' => $dateString,
                    'value' => $revenueOnDate,
                    'name' => $date->format('M d')
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_users' => $totalUsers,
                        'new_users_today' => $newUsersToday,
                        'new_users_week' => $newUsersThisWeek,
                        'new_users_month' => $newUsersThisMonth,
                        'total_listings' => $totalListings,
                        'active_listings' => $activeListings,
                        'pending_listings' => $pendingListings,
                        'new_listings_today' => $newListingsToday,
                        'new_listings_week' => $newListingsThisWeek,
                        'total_sellers' => $totalSellers,
                        'new_sellers_month' => $newSellersThisMonth,
                        'active_subscriptions' => $activeSubscriptions,
                        'total_revenue' => $totalRevenue,
                    ],
                    'time_series' => [
                        'user_growth' => $userGrowthData,
                        'listing_growth' => $listingGrowthData,
                        'revenue_data' => $revenueData,
                    ],
                    'period' => [
                        'days' => $days,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => Carbon::now()->toDateString(),
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dashboardAnalyticsExtended(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->get('time_range', '30d');
            $days = (int) str_replace('d', '', $timeRange);
            $startDate = Carbon::now()->subDays($days);

            // Generate time-series data for user growth
            $userGrowth = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = User::whereDate('created_at', '<=', $date)->count();
                $userGrowth[] = [
                    'name' => $date->format('M d'),
                    'value' => $count,
                    'date' => $date->toISOString()
                ];
            }

            // Generate time-series data for listings
            $listingMetrics = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = EquipmentListing::whereDate('created_at', $date)->count();
                $listingMetrics[] = [
                    'name' => $date->format('M d'),
                    'value' => $count,
                    'date' => $date->toISOString()
                ];
            }

            // Generate time-series data for revenue (from financial transactions only)
            $revenueData = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);

                // Revenue from financial transactions only (to match Financial Reports)
                $revenue = \DB::table('financial_transactions')
                    ->whereDate('transaction_date', $date)
                    ->where('transaction_type', 'income')
                    ->sum('amount');

                $revenueData[] = [
                    'name' => $date->format('M d'),
                    'value' => (int) $revenue,
                    'date' => $date->toISOString()
                ];
            }

            // Generate time-series data for engagement (views + messages)
            $engagementData = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $views = EquipmentListing::whereDate('updated_at', $date)->sum('view_count');
                $messages = \DB::table('messages')->whereDate('created_at', $date)->count();
                $engagementData[] = [
                    'name' => $date->format('M d'),
                    'value' => (int) ($views + $messages * 10), // Weight messages more
                    'date' => $date->toISOString()
                ];
            }

            // Category analytics
            $categoryStats = EquipmentListing::join('equipment_categories', 'equipment_listings.category_id', '=', 'equipment_categories.id')
                ->select('equipment_categories.name', \DB::raw('count(*) as count'))
                ->groupBy('equipment_categories.id', 'equipment_categories.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'name' => $item->name,
                        'value' => $item->count
                    ];
                });

            // Top sellers
            $topSellers = EquipmentListing::join('user_profiles', 'equipment_listings.seller_id', '=', 'user_profiles.user_id')
                ->select('user_profiles.full_name', \DB::raw('count(*) as listing_count'))
                ->where('equipment_listings.status', 'active')
                ->groupBy('user_profiles.user_id', 'user_profiles.full_name')
                ->orderBy('listing_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'name' => $item->full_name ?: 'Unknown Seller',
                        'value' => $item->listing_count
                    ];
                });

            // Calculate summary metrics
            $totalUsers = User::count();
            $totalListings = EquipmentListing::count();
            $activeListings = EquipmentListing::where('status', 'active')->count();

            // Calculate total revenue from financial transactions only (to match Financial Reports)
            $totalRevenue = \DB::table('financial_transactions')
                ->where('transaction_type', 'income')
                ->sum('amount');

            $totalViews = EquipmentListing::sum('view_count');

            // Recent activity
            $recentUsers = User::with('profile')
                ->latest()
                ->limit(5)
                ->get();

            $recentListings = EquipmentListing::with(['category'])
                ->latest()
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'userGrowth' => $userGrowth,
                    'revenueData' => $revenueData,
                    'listingMetrics' => $listingMetrics,
                    'engagementData' => $engagementData,
                    'topCategories' => $categoryStats,
                    'topSellers' => $topSellers,
                    'summary' => [
                        'total_users' => $totalUsers,
                        'total_listings' => $totalListings,
                        'active_listings' => $activeListings,
                        'total_revenue' => (int) $totalRevenue,
                        'total_views' => (int) $totalViews,
                    ],
                    'recent_activity' => [
                        'users' => $recentUsers,
                        'listings' => $recentListings,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllSystemSettings(): JsonResponse
    {
        try {
            $settings = SystemSetting::all();

            return response()->json([
                'success' => true,
                'data' => $settings->map(function ($setting) {
                    return [
                        'key' => $setting->key,
                        'value' => $setting->value,
                        'description' => $setting->description,
                        'category' => $setting->category ?? 'general',
                        'is_public' => $setting->is_public ?? false,
                        'updated_at' => $setting->updated_at,
                        'updater_email' => $setting->updatedBy?->email ?? null,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSystemSetting(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'key' => 'required|string|max:255',
                'value' => 'required',
                'description' => 'nullable|string|max:1000',
                'category' => 'nullable|string|max:100',
                'is_public' => 'nullable|boolean',
            ]);

            $setting = SystemSetting::updateOrCreate(
                ['key' => $validated['key']],
                [
                    'value' => $validated['value'],
                    'description' => $validated['description'] ?? '',
                    'category' => $validated['category'] ?? 'general',
                    'is_public' => $validated['is_public'] ?? false,
                    'updated_by' => auth()->id(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'System setting updated successfully',
                'data' => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'description' => $setting->description,
                    'category' => $setting->category,
                    'is_public' => $setting->is_public,
                    'updated_at' => $setting->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update system setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteSystemSetting($key): JsonResponse
    {
        try {
            $setting = SystemSetting::where('key', $key)->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'System setting not found',
                ], 404);
            }

            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'System setting deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete system setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getBannerRevenueAnalytics(): JsonResponse
    {
        try {
            // Get real banner revenue analytics from banner purchase requests
            $monthlyRevenue = [];
            $today = Carbon::now();

            for ($i = 11; $i >= 0; $i--) {
                $date = $today->copy()->subMonths($i);
                $startOfMonth = $date->copy()->startOfMonth();
                $endOfMonth = $date->copy()->endOfMonth();

                // Get confirmed banner purchases for this month
                $confirmedPurchases = BannerPurchaseRequest::where('payment_status', 'confirmed')
                    ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                    ->get();

                $monthlyBannerRevenue = $confirmedPurchases->sum('total_price');
                $monthlyBannerCount = $confirmedPurchases->count();

                $monthlyRevenue[] = [
                    'month' => $date->format('M Y'),
                    'revenue' => $monthlyBannerRevenue,
                    'banner_count' => $monthlyBannerCount,
                    'avg_per_banner' => $monthlyBannerCount > 0 ? round($monthlyBannerRevenue / $monthlyBannerCount, 2) : 0
                ];
            }

            $totalRevenue = array_sum(array_column($monthlyRevenue, 'revenue'));
            $totalBanners = array_sum(array_column($monthlyRevenue, 'banner_count'));

            // Get revenue by position for top positions analysis
            $positionRevenue = BannerPurchaseRequest::where('payment_status', 'confirmed')
                ->select('banner_position', DB::raw('SUM(total_price) as revenue'), DB::raw('COUNT(*) as count'))
                ->groupBy('banner_position')
                ->orderBy('revenue', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'position' => $item->banner_position,
                        'revenue' => $item->revenue,
                        'count' => $item->count
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'total_banners' => $totalBanners,
                    'average_per_banner' => $totalBanners > 0 ? round($totalRevenue / $totalBanners, 2) : 0,
                    'monthly_data' => $monthlyRevenue,
                    'top_positions' => $positionRevenue
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banner revenue analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getBannerPricingTiers(): JsonResponse
    {
        try {
            // Return banner pricing tiers configuration
            $pricingTiers = [
                [
                    'id' => 1,
                    'name' => 'Premium',
                    'positions' => ['top', 'sidebar-featured'],
                    'base_price' => 100.00,
                    'duration_multiplier' => 1.0,
                    'features' => ['Prime placement', 'Analytics included', 'Click tracking']
                ],
                [
                    'id' => 2,
                    'name' => 'Standard',
                    'positions' => ['middle', 'sidebar-top', 'sidebar-middle'],
                    'base_price' => 60.00,
                    'duration_multiplier' => 0.8,
                    'features' => ['Good visibility', 'Basic analytics']
                ],
                [
                    'id' => 3,
                    'name' => 'Basic',
                    'positions' => ['bottom', 'sidebar-bottom', 'sidebar-footer'],
                    'base_price' => 30.00,
                    'duration_multiplier' => 0.6,
                    'features' => ['Cost-effective', 'Standard placement']
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $pricingTiers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banner pricing tiers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Listing Moderation Methods
    public function getListingsForModeration(Request $request): JsonResponse
    {
        try {
            $query = EquipmentListing::with(['category', 'seller'])
                ->orderBy('created_at', 'desc');

            // Apply status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Apply expiration filter
            if ($request->has('expiration')) {
                switch ($request->expiration) {
                    case 'expired':
                        $query->where('expires_at', '<', now());
                        break;
                    case 'expiring_soon':
                        $query->whereBetween('expires_at', [now(), now()->addWeek()]);
                        break;
                    case 'no_expiration':
                        $query->whereNull('expires_at');
                        break;
                }
            }

            $listings = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $listings->items(),
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
                'message' => 'Failed to fetch listings for moderation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function moderateListing(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:approve,reject,flag,suspend',
                'reason' => 'nullable|string|max:500',
            ]);

            $listing = EquipmentListing::with(['category', 'seller'])->findOrFail($id);

            // Update listing status based on action
            switch ($validated['action']) {
                case 'approve':
                    $listing->status = 'active';
                    break;
                case 'reject':
                    $listing->status = 'rejected';
                    break;
                case 'flag':
                    $listing->status = 'flagged';
                    break;
                case 'suspend':
                    $listing->status = 'suspended';
                    break;
            }

            $listing->moderated_at = now();
            $listing->moderated_by = auth()->id();
            $listing->moderation_reason = $validated['reason'];
            $listing->save();

            return response()->json([
                'success' => true,
                'message' => "Listing {$validated['action']}d successfully",
                'data' => $listing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to moderate listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function extendListingExpiration(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'additional_days' => 'required|integer|min:1|max:365',
                'reason' => 'nullable|string|max:500',
            ]);

            $listing = EquipmentListing::with(['category', 'seller'])->findOrFail($id);

            if ($listing->expires_at) {
                $listing->expires_at = Carbon::parse($listing->expires_at)->addDays($validated['additional_days']);
            } else {
                $listing->expires_at = now()->addDays($validated['additional_days']);
            }

            $listing->save();

            return response()->json([
                'success' => true,
                'message' => "Listing expiration extended by {$validated['additional_days']} days",
                'data' => $listing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend listing expiration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function runAutoCleanup(): JsonResponse
    {
        try {
            $expiredListings = EquipmentListing::where('expires_at', '<', now())
                ->where('status', '!=', 'expired')
                ->count();

            EquipmentListing::where('expires_at', '<', now())
                ->where('status', '!=', 'expired')
                ->update([
                    'status' => 'expired',
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => "Auto cleanup completed. {$expiredListings} listings marked as expired",
                'data' => ['affected_count' => $expiredListings],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run auto cleanup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getModerationStats(): JsonResponse
    {
        try {
            $statusCounts = EquipmentListing::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $expiringCount = EquipmentListing::where('status', 'active')
                ->whereBetween('expires_at', [now(), now()->addWeek()])
                ->count();

            $expiredCount = EquipmentListing::where('expires_at', '<', now())
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'statusCounts' => $statusCounts,
                    'expiringCount' => $expiringCount,
                    'expiredCount' => $expiredCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch moderation statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createEmailVerification(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'email' => 'required|email',
            ]);

            $user = User::findOrFail($validated['user_id']);

            // Generate a 6-digit verification code
            $verificationCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Store the verification code (in a real app, you'd store this in database)
            // For now, we'll just return it - in production, you'd send via email

            return response()->json([
                'success' => true,
                'message' => 'Email verification code created successfully',
                'data' => [
                    'verification_code' => $verificationCode, // In production, don't return this
                    'email' => $validated['email'],
                    'expires_at' => now()->addMinutes(15)->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create email verification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyEmailCode(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'verification_code' => 'required|string|size:6',
            ]);

            $user = User::findOrFail($validated['user_id']);

            // In a real implementation, you'd verify against stored code
            // For now, we'll accept any 6-digit code as valid
            if (strlen($validated['verification_code']) === 6 && is_numeric($validated['verification_code'])) {
                // Mark email as verified
                $user->email_verified_at = now();
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Email verified successfully',
                    'data' => [
                        'verified' => true,
                        'verified_at' => $user->email_verified_at,
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify email code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateSellerInvoice(Request $request): JsonResponse
    {
        try {
            // Clean and prepare request data
            $requestData = $request->all();

            // Handle plan_id - be very flexible
            if (isset($requestData['plan_id'])) {
                if ($requestData['plan_id'] === '' || $requestData['plan_id'] === '0' || $requestData['plan_id'] === 0 || $requestData['plan_id'] === null || empty($requestData['plan_id'])) {
                    $requestData['plan_id'] = null;
                } else {
                    $requestData['plan_id'] = (int) $requestData['plan_id'];
                }
            } else {
                // If plan_id is not set at all, make it null
                $requestData['plan_id'] = null;
            }

            // Ensure application_id is an integer or null
            if (isset($requestData['application_id']) && $requestData['application_id']) {
                $requestData['application_id'] = (int) $requestData['application_id'];
            } else {
                $requestData['application_id'] = null;
            }

            // Ensure user_id is an integer if provided
            if (isset($requestData['user_id'])) {
                $requestData['user_id'] = (int) $requestData['user_id'];
            }

            // Clean up custom_items if present
            if (isset($requestData['custom_items']) && is_array($requestData['custom_items'])) {
                foreach ($requestData['custom_items'] as $key => $item) {
                    if (isset($item['amount'])) {
                        $requestData['custom_items'][$key]['amount'] = (float) $item['amount'];
                    }
                    if (isset($item['quantity'])) {
                        $requestData['custom_items'][$key]['quantity'] = (int) $item['quantity'];
                    }
                }
            }

            // Clean up totals
            if (isset($requestData['totals']) && is_array($requestData['totals'])) {
                if (isset($requestData['totals']['subtotal'])) {
                    $requestData['totals']['subtotal'] = (float) $requestData['totals']['subtotal'];
                }
                if (isset($requestData['totals']['total'])) {
                    $requestData['totals']['total'] = (float) $requestData['totals']['total'];
                }
            }

            // Provide default due_date if missing (14 days from now)
            if (empty($requestData['due_date'])) {
                $requestData['due_date'] = now()->addDays(14)->format('Y-m-d');
            }

            $validated = validator($requestData, [
                'application_id' => 'nullable|integer|exists:seller_applications,id',
                'user_id' => 'required_without:application_id|integer|exists:users,id',
                'plan_id' => 'nullable',
                'custom_items' => 'nullable|array',
                'custom_items.*.description' => 'nullable|string|max:255',
                'custom_items.*.amount' => 'nullable|numeric|min:0',
                'custom_items.*.quantity' => 'nullable|integer|min:1',
                'due_date' => 'nullable|date',
                'notes' => 'nullable|string|max:1000',
                'terms_and_conditions' => 'nullable|string|max:2000',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_type' => 'nullable|string',
                'invoice_type' => 'nullable|string|max:50',
                'totals' => 'required|array',
                'totals.subtotal' => 'required|numeric|min:0',
                'totals.total' => 'required|numeric|min:0',
            ])->validate();

            // Get the seller application - use a more flexible approach
            $application = null;
            $seller = null;

            // First, try to get application if application_id is provided
            if (!empty($validated['application_id'])) {
                try {
                    if (class_exists('App\Models\SellerApplication')) {
                        $application = \App\Models\SellerApplication::with('user')->find($validated['application_id']);
                        if ($application) {
                            $seller = $application->user;
                        }
                    }
                } catch (\Exception $e) {
                    // Application lookup failed
                }
            }

            // If no seller from application, try user_id
            if (!$seller && !empty($validated['user_id'])) {
                $seller = \App\Models\User::find($validated['user_id']);
            }

            // Final check - if still no seller, return error
            if (!$seller) {
                return response()->json([
                    'success' => false,
                    'message' => 'User or seller not found',
                ], 404);
            }

            // Get plan details if plan_id is provided
            $plan = null;
            if ($validated['plan_id']) {
                try {
                    if (class_exists('App\Models\SubscriptionPlan')) {
                        $plan = \App\Models\SubscriptionPlan::find($validated['plan_id']);
                    }
                } catch (\Exception $e) {
                    // Plan lookup failed, continue without plan
                }
            }

            // Generate unique invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber();

            // Use provided totals or calculate from scratch
            $subtotal = $validated['totals']['subtotal'];
            $totalAmount = $validated['totals']['total'];
            $taxRate = $validated['tax_rate'] ?? 0;
            $taxAmount = $subtotal * ($taxRate / 100);
            $discount = $validated['discount_amount'] ?? 0;

            // Prepare invoice items
            $items = [];

            // Add plan as an item if selected
            if ($plan) {
                $items[] = [
                    'description' => "Subscription Plan: {$plan->name}",
                    'amount' => $plan->price,
                    'quantity' => 1,
                    'total' => $plan->price
                ];
            }

            // Add custom items
            if (!empty($validated['custom_items'])) {
                foreach ($validated['custom_items'] as $item) {
                    $quantity = $item['quantity'] ?? 1;
                    $items[] = [
                        'description' => $item['description'],
                        'amount' => $item['amount'],
                        'quantity' => $quantity,
                        'total' => $item['amount'] * $quantity
                    ];
                }
            }

            // Create invoice in database
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $seller->id,
                'seller_application_id' => $application?->id,
                'plan_id' => $validated['plan_id'] ?: null, // Convert 0 to null
                'amount' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discount,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'total_amount' => $totalAmount,
                'due_date' => $validated['due_date'],
                'status' => 'pending',
                'invoice_type' => $validated['invoice_type'] ?? ($application ? 'seller_application' : 'other'),
                'items' => $items,
                'notes' => $validated['notes'] ?? '',
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? 'Payment is due within 30 days of invoice date.',
                'company_name' => $application?->company_name ?? null,
                'generated_by' => auth()->user()->name ?? 'Admin',
            ]);

            // Load relationships for response
            $invoice->load(['user', 'sellerApplication', 'subscriptionPlan']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated successfully',
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate seller invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getInvoices(Request $request): JsonResponse
    {
        try {
            $query = Invoice::with(['user', 'user.profile', 'sellerApplication', 'subscriptionPlan'])
                ->select('invoices.*'); // Ensure all invoice fields including payment_proof_url are selected

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('invoice_type') && $request->invoice_type !== 'all') {
                $query->where('invoice_type', $request->invoice_type);
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $perPage = $request->get('per_page', 20);
            $invoices = $query->paginate($perPage);

            // Update overdue invoices
            Invoice::where('status', 'pending')
                   ->where('due_date', '<', now())
                   ->update(['status' => 'overdue']);

            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                    'last_page' => $invoices->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getInvoiceStats(): JsonResponse
    {
        try {
            // Update overdue invoices first
            Invoice::where('status', 'pending')
                   ->where('due_date', '<', now())
                   ->update(['status' => 'overdue']);

            $stats = [
                'total' => Invoice::count(),
                'pending' => Invoice::where('status', 'pending')->count(),
                'paid' => Invoice::where('status', 'paid')->count(),
                'overdue' => Invoice::where('status', 'overdue')->count(),
                'processing' => Invoice::where('status', 'processing')->count(),
                'cancelled' => Invoice::where('status', 'cancelled')->count(),
                // Total Revenue = only paid and approved invoices
                'total_amount' => Invoice::where('status', 'paid')->sum('total_amount'),
                // Unpaid Revenue = pending + overdue + processing
                'unpaid_amount' => Invoice::whereIn('status', ['pending', 'overdue', 'processing'])->sum('total_amount'),
                'pending_amount' => Invoice::where('status', 'pending')->sum('total_amount'),
                'paid_amount' => Invoice::where('status', 'paid')->sum('total_amount'),
                'overdue_amount' => Invoice::where('status', 'overdue')->sum('total_amount'),
                'processing_amount' => Invoice::where('status', 'processing')->sum('total_amount'),
            ];

            // Monthly revenue for current year
            $monthlyRevenue = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyRevenue[] = [
                    'month' => date('M', mktime(0, 0, 0, $month, 1)),
                    'revenue' => Invoice::where('status', 'paid')
                                      ->whereYear('paid_at', now()->year)
                                      ->whereMonth('paid_at', $month)
                                      ->sum('total_amount')
                ];
            }

            $stats['monthly_revenue'] = $monthlyRevenue;

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoice statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getInvoice($id): JsonResponse
    {
        try {
            $invoice = Invoice::with(['user', 'sellerApplication', 'subscriptionPlan'])
                             ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function sendSellerInvoice($id): JsonResponse
    {
        try {
            $invoice = Invoice::with('user')->findOrFail($id);

            // Mark as sent
            $invoice->markAsSent();

            // In a real application, you would send the actual email here
            // For now, we'll just simulate sending

            return response()->json([
                'success' => true,
                'message' => 'Invoice sent successfully',
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadInvoice($id)
    {
        try {
            $invoice = Invoice::with(['user', 'sellerApplication', 'subscriptionPlan'])
                             ->findOrFail($id);

            // In a real application, you would generate a PDF here
            // For now, we'll return a simple response

            return response()->json([
                'success' => true,
                'message' => 'Invoice download prepared',
                'download_url' => "/admin/invoices/{$id}/pdf",
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare invoice download',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // User-facing invoice methods
    public function getUserInvoices(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();

            $query = Invoice::with(['sellerApplication', 'subscriptionPlan'])
                           ->where('user_id', $userId);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $perPage = $request->get('per_page', 10);
            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                    'last_page' => $invoices->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch your invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserInvoice($id): JsonResponse
    {
        try {
            $userId = auth()->id();

            $invoice = Invoice::with(['sellerApplication', 'subscriptionPlan'])
                             ->where('user_id', $userId)
                             ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or access denied',
            ], 404);
        }
    }

    public function markInvoiceAsPaid(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'payment_reference' => 'nullable|string|max:255',
                'payment_method' => 'nullable|string|max:100',
                'payment_notes' => 'nullable|string|max:500',
            ]);

            // Admin can mark any invoice as paid
            $invoice = Invoice::where('status', 'pending')
                             ->findOrFail($id);

            // Update invoice status and payment info
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'notes' => ($invoice->notes ?? '') . "\n\nPayment Info:\n" .
                          "Reference: " . ($validated['payment_reference'] ?? 'N/A') . "\n" .
                          "Method: " . ($validated['payment_method'] ?? 'N/A') . "\n" .
                          "Notes: " . ($validated['payment_notes'] ?? 'N/A')
            ]);

            // Create financial transaction for the payment
            \App\Models\FinancialTransaction::create([
                'transaction_reference' => 'INV-' . $invoice->invoice_number . '-' . time(),
                'transaction_type' => 'income',
                'category' => $this->getInvoiceCategory($invoice),
                'amount' => $invoice->total_amount,
                'description' => "Payment received for Invoice #{$invoice->invoice_number}",
                'notes' => "Invoice payment - " . ($invoice->description ?? 'Service Invoice'),
                'transaction_date' => now(),
                'payment_method' => $validated['payment_method'] ?? 'unknown',
                'payment_reference' => $validated['payment_reference'] ?? $invoice->invoice_number,
                'user_id' => $invoice->user_id,
                'recorded_by' => auth()->id() ?? 1,
                'payment_status' => 'completed',
                'related_model_type' => 'App\\Models\\Invoice',
                'related_model_id' => $invoice->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as paid successfully and transaction recorded',
                'data' => $invoice->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determine the appropriate financial category for an invoice
     */
    private function getInvoiceCategory($invoice): string
    {
        // Map invoice types to financial categories
        $categoryMap = [
            'subscription' => 'subscription_revenue',
            'service' => 'maintenance_services',
            'equipment' => 'equipment_sales',
            'consultation' => 'consultation_services',
            'installation' => 'installation_services',
            'maintenance' => 'maintenance_services',
            'parts' => 'spare_parts_sales',
            'listing' => 'featured_listing_revenue',
            'banner' => 'banner_ad_revenue',
        ];

        // Get category from invoice type or description
        $invoiceType = $invoice->invoice_type ?? 'service';

        // Try to match based on invoice type
        if (isset($categoryMap[$invoiceType])) {
            return $categoryMap[$invoiceType];
        }

        // Try to match based on description keywords
        $description = strtolower($invoice->description ?? '');
        foreach ($categoryMap as $keyword => $category) {
            if (strpos($description, $keyword) !== false) {
                return $category;
            }
        }

        // Default to general service revenue
        return 'other_income';
    }

    /**
     * Sync historical invoice payments to financial transactions
     */
    public function syncInvoiceTransactions(): JsonResponse
    {
        try {
            // Get all paid invoices that don't have corresponding transactions
            $paidInvoices = \App\Models\Invoice::where('status', 'paid')
                ->whereNotExists(function($query) {
                    $query->select(\DB::raw(1))
                          ->from('financial_transactions')
                          ->whereRaw('financial_transactions.related_model_type = ? AND financial_transactions.related_model_id = invoices.id', ['App\\Models\\Invoice']);
                })
                ->get();

            $syncedCount = 0;

            foreach ($paidInvoices as $invoice) {
                // Create financial transaction for each paid invoice
                \App\Models\FinancialTransaction::create([
                    'transaction_reference' => 'SYNC-INV-' . $invoice->invoice_number . '-' . $invoice->id,
                    'transaction_type' => 'income',
                    'category' => $this->getInvoiceCategory($invoice),
                    'amount' => $invoice->total_amount,
                    'description' => "Payment received for Invoice #{$invoice->invoice_number} (Historical Sync)",
                    'notes' => "Historical invoice payment sync - " . ($invoice->description ?? 'Service Invoice'),
                    'transaction_date' => $invoice->paid_at ?? $invoice->updated_at,
                    'payment_method' => 'unknown',
                    'payment_reference' => $invoice->invoice_number,
                    'user_id' => $invoice->user_id,
                    'recorded_by' => auth()->id() ?? 1,
                    'payment_status' => 'completed',
                    'related_model_type' => 'App\\Models\\Invoice',
                    'related_model_id' => $invoice->id
                ]);

                $syncedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$syncedCount} historical invoice payments to financial transactions",
                'synced_count' => $syncedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync invoice transactions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function submitPaymentProof(Request $request, $id): JsonResponse
    {
        try {
            $userId = auth()->id();

            $validated = $request->validate([
                'payment_reference' => 'required|string|max:255',
                'payment_method' => 'required|string|in:bank_transfer,online_payment,mobile_money,cash,check',
                'payment_notes' => 'nullable|string|max:1000',
                'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png,gif|max:5120', // 5MB max
            ]);

            $invoice = Invoice::where('user_id', $userId)
                             ->whereIn('status', ['pending', 'overdue'])
                             ->findOrFail($id);

            // Handle file upload to storage
            $proofPublicId = null;
            $proofUrl = null;

            if ($request->hasFile('payment_proof')) {
                $fileStorageService = app(\App\Services\FileStorageService::class);

                // Use uploadFile method for documents (supports PDFs and images)
                $uploadResult = $fileStorageService->uploadFile(
                    $request->file('payment_proof'),
                    'documents', // Use documents folder
                    [
                        'tags' => 'payment_proof,invoice_' . $invoice->id,
                        'public_id' => 'payment_proof_' . $invoice->id . '_' . time()
                    ]
                );

                if ($uploadResult['success']) {
                    $proofPublicId = $uploadResult['data']['public_id'];
                    $proofUrl = $uploadResult['data']['url'];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload payment proof document',
                        'error' => $uploadResult['error']
                    ], 400);
                }
            }

            // Update invoice with payment proof data and change status to processing
            $invoice->update([
                'status' => 'processing',
                'payment_reference' => $validated['payment_reference'],
                'payment_method' => $validated['payment_method'],
                'payment_notes' => $validated['payment_notes'],
                'payment_proof_public_id' => $proofPublicId,
                'payment_proof_url' => $proofUrl,
                'payment_submitted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment proof submitted successfully. Admin will review and update status.',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'payment_reference' => $invoice->payment_reference,
                    'payment_method' => $invoice->payment_method,
                    'payment_submitted_at' => $invoice->payment_submitted_at,
                    'payment_proof_url' => $proofUrl
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Payment proof submission failed', [
                'invoice_id' => $id,
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit payment proof. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function downloadUserInvoice($id)
    {
        try {
            $userId = auth()->id();

            $invoice = Invoice::with(['sellerApplication', 'subscriptionPlan'])
                             ->where('user_id', $userId)
                             ->findOrFail($id);

            // In a real application, you would generate a PDF here
            // For now, we'll return a simple response

            return response()->json([
                'success' => true,
                'message' => 'Invoice download prepared',
                'download_url' => "/user/invoices/{$id}/pdf",
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare invoice download or access denied',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an invoice (user can delete any of their own invoices)
     */
    public function deleteUserInvoice($id): JsonResponse
    {
        try {
            $userId = auth()->id();

            $invoice = Invoice::where('user_id', $userId)->findOrFail($id);

            // Store invoice number for response message
            $invoiceNumber = $invoice->invoice_number;

            // Delete the invoice
            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => "Invoice {$invoiceNumber} has been deleted successfully",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found or access denied',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve or reject payment proof for an invoice (admin only)
     */
    public function approvePayment(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'action' => 'required|string|in:approve,reject',
                'notes' => 'nullable|string|max:500'
            ]);

            $invoice = Invoice::where('status', 'processing')
                             ->findOrFail($id);

            if ($validated['action'] === 'approve') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'notes' => $invoice->notes . "\n\nPayment approved by admin: " . auth()->user()->name . " on " . now()
                ]);

                $message = 'Payment proof approved and invoice marked as paid';
            } else {
                $invoice->update([
                    'status' => 'pending',
                    'payment_reference' => null,
                    'payment_method' => null,
                    'payment_notes' => null,
                    'payment_proof_public_id' => null,
                    'payment_proof_url' => null,
                    'payment_submitted_at' => null,
                    'notes' => $invoice->notes . "\n\nPayment proof rejected by admin: " . auth()->user()->name . " on " . now() .
                               ($validated['notes'] ? "\nReason: " . $validated['notes'] : '')
                ]);

                $message = 'Payment proof rejected and invoice reset to pending';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'paid_at' => $invoice->paid_at
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Payment approval failed', [
                'invoice_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment approval',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get payment proof image for an invoice
     */
    public function getPaymentProof($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check if payment proof exists
            if (!$invoice->payment_proof) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment proof found for this invoice'
                ], 404);
            }

            // If using Cloudinary or external storage, return the URL
            if ($invoice->payment_proof_url) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'url' => $invoice->payment_proof_url,
                        'type' => 'url'
                    ]
                ]);
            }

            // If stored locally, return the file
            $path = storage_path('app/public/payment_proofs/' . $invoice->payment_proof);

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment proof file not found'
                ], 404);
            }

            return response()->file($path);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment proof',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system status information
     */
    public function getSystemStatus(): JsonResponse
    {
        try {
            // Check database connection
            $dbStatus = true;
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $dbStatus = false;
            }

            // Check storage
            $storageStatus = is_writable(storage_path());

            // Check cache
            $cacheStatus = true;
            try {
                cache()->put('test', 'test', 1);
                cache()->forget('test');
            } catch (\Exception $e) {
                $cacheStatus = false;
            }

            // Get PHP version
            $phpVersion = phpversion();

            // Get Laravel version
            $laravelVersion = app()->version();

            // Get memory usage
            $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2) . ' MB';
            $memoryPeak = round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';

            // Get disk space
            $diskFree = round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB';
            $diskTotal = round(disk_total_space('/') / 1024 / 1024 / 1024, 2) . ' GB';

            // Get uptime (if available)
            $uptime = 'N/A';
            if (PHP_OS_FAMILY === 'Linux') {
                $uptime = trim(shell_exec("uptime -p"));
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'operational',
                    'services' => [
                        'database' => $dbStatus ? 'operational' : 'down',
                        'storage' => $storageStatus ? 'operational' : 'limited',
                        'cache' => $cacheStatus ? 'operational' : 'down',
                        'queue' => 'operational', // Placeholder
                        'mail' => 'operational', // Placeholder
                    ],
                    'system' => [
                        'php_version' => $phpVersion,
                        'laravel_version' => $laravelVersion,
                        'memory_usage' => $memoryUsage,
                        'memory_peak' => $memoryPeak,
                        'disk_free' => $diskFree,
                        'disk_total' => $diskTotal,
                        'uptime' => $uptime,
                        'environment' => config('app.env'),
                        'debug_mode' => config('app.debug'),
                        'timezone' => config('app.timezone'),
                    ],
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update listing priority
     */
    public function updateListingPriority(Request $request, $listingId): JsonResponse
    {
        try {
            $request->validate([
                'priority' => 'required|integer|min:0|max:100'
            ]);

            $listing = EquipmentListing::findOrFail($listingId);
            $listing->priority = $request->priority;
            $listing->save();

            return response()->json([
                'success' => true,
                'message' => 'Listing priority updated successfully',
                'data' => [
                    'id' => $listing->id,
                    'priority' => $listing->priority,
                    'title' => $listing->title
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing priority',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update featured status
     */
    public function updateFeaturedStatus(Request $request, $listingId): JsonResponse
    {
        try {
            $request->validate([
                'is_featured' => 'required|boolean',
                'reason' => 'nullable|string|max:500'
            ]);

            $listing = EquipmentListing::findOrFail($listingId);
            $listing->is_featured = $request->is_featured;

            // Set featured until date if featuring
            if ($request->is_featured) {
                $listing->featured_until = now()->addDays(30); // Default 30 days
            } else {
                $listing->featured_until = null;
            }

            $listing->save();

            // Log the action for audit trail
            \Log::info('Admin featured status change', [
                'admin_id' => auth()->id(),
                'listing_id' => $listingId,
                'is_featured' => $request->is_featured,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->is_featured ? 'Listing featured successfully' : 'Featured status removed successfully',
                'data' => [
                    'id' => $listing->id,
                    'is_featured' => $listing->is_featured,
                    'featured_until' => $listing->featured_until,
                    'title' => $listing->title
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update featured status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk update listing priorities
     */
    public function bulkUpdatePriority(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'listing_ids' => 'required|array|min:1',
                'listing_ids.*' => 'integer|exists:equipment_listings,id',
                'priority' => 'required|integer|min:0|max:100'
            ]);

            $updated = EquipmentListing::whereIn('id', $request->listing_ids)
                ->update(['priority' => $request->priority]);

            return response()->json([
                'success' => true,
                'message' => "Updated priority for {$updated} listings",
                'data' => [
                    'updated_count' => $updated,
                    'priority' => $request->priority
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk update priorities',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get priority statistics
     */
    public function getPriorityStatistics(): JsonResponse
    {
        try {
            $stats = [
                'critical' => EquipmentListing::where('priority', '>=', 90)->count(),
                'high' => EquipmentListing::whereBetween('priority', [70, 89])->count(),
                'medium' => EquipmentListing::whereBetween('priority', [40, 69])->count(),
                'low' => EquipmentListing::whereBetween('priority', [1, 39])->count(),
                'normal' => EquipmentListing::where('priority', 0)->orWhereNull('priority')->count(),
                'total_with_priority' => EquipmentListing::where('priority', '>', 0)->count(),
                'total_listings' => EquipmentListing::count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch priority statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get featured statistics
     */
    public function getFeaturedStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_featured' => EquipmentListing::where('is_featured', true)->count(),
                'active_featured' => EquipmentListing::where('is_featured', true)
                    ->where('status', 'active')
                    ->count(),
                'featured_by_category' => EquipmentListing::where('is_featured', true)
                    ->with('category')
                    ->get()
                    ->groupBy('category.name')
                    ->map(function ($items) {
                        return $items->count();
                    }),
                'expiring_soon' => EquipmentListing::where('is_featured', true)
                    ->where('featured_until', '<=', now()->addDays(7))
                    ->count(),
                'total_listings' => EquipmentListing::count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete equipment listing (admin can delete any listing)
     */
    public function deleteListing($id): JsonResponse
    {
        try {
            $listing = EquipmentListing::with(['category', 'seller'])->findOrFail($id);

            // Store listing details for response
            $listingData = [
                'id' => $listing->id,
                'title' => $listing->title,
                'seller_name' => $listing->user->name ?? 'Unknown',
            ];

            // Delete the listing
            $listing->delete();

            return response()->json([
                'success' => true,
                'message' => 'Equipment listing deleted successfully',
                'data' => $listingData
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment listing not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete equipment listing',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get users by subscription plan
     */
    public function getUsersBySubscriptionPlan(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:subscription_plans,id',
                'status' => 'nullable|string|in:active,expired,cancelled',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $query = User::whereHas('subscriptions', function ($q) use ($validated) {
                $q->where('plan_id', $validated['plan_id']);

                if (isset($validated['status'])) {
                    $q->where('status', $validated['status']);
                }
            })->with(['subscriptions' => function ($q) use ($validated) {
                $q->where('plan_id', $validated['plan_id'])
                  ->orderBy('created_at', 'desc');
            }]);

            $users = $query->paginate($validated['per_page'] ?? 20);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users by subscription plan',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get API keys for admin management
     */
    public function getApiKeys(): JsonResponse
    {
        try {
            $apiKeys = DB::table('personal_access_tokens')
                ->select(['id', 'name', 'tokenable_id', 'tokenable_type', 'abilities', 'last_used_at', 'created_at'])
                ->where('tokenable_type', User::class)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $apiKeys->items(),
                'meta' => [
                    'current_page' => $apiKeys->currentPage(),
                    'per_page' => $apiKeys->perPage(),
                    'total' => $apiKeys->total(),
                    'last_page' => $apiKeys->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch API keys',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create new API key for user
     */
    public function createApiKey(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'name' => 'required|string|max:255',
                'abilities' => 'nullable|array',
                'abilities.*' => 'string'
            ]);

            $user = User::findOrFail($validated['user_id']);
            $abilities = $validated['abilities'] ?? ['*'];

            $token = $user->createToken($validated['name'], $abilities);

            return response()->json([
                'success' => true,
                'message' => 'API key created successfully',
                'data' => [
                    'token' => $token->plainTextToken,
                    'name' => $validated['name'],
                    'abilities' => $abilities,
                    'user_id' => $user->id,
                    'created_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create API key',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey(Request $request, $tokenId): JsonResponse
    {
        try {
            $token = DB::table('personal_access_tokens')
                ->where('id', $tokenId)
                ->first();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not found'
                ], 404);
            }

            DB::table('personal_access_tokens')
                ->where('id', $tokenId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'API key revoked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke API key',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get branding settings
     */
    public function getBrandingSettings(): JsonResponse
    {
        try {
            $brandingSettings = [
                'site_name' => SystemSetting::getValue('site_name', 'Marine.ng'),
                'site_logo' => SystemSetting::getValue('site_logo', ''),
                'site_favicon' => SystemSetting::getValue('site_favicon', ''),
                'primary_color' => SystemSetting::getValue('primary_color', '#1e40af'),
                'secondary_color' => SystemSetting::getValue('secondary_color', '#64748b'),
                'footer_text' => SystemSetting::getValue('footer_text', ''),
                'contact_email' => SystemSetting::getValue('contact_email', 'contact@marine.ng'),
                'support_phone' => SystemSetting::getValue('support_phone', ''),
                'social_facebook' => SystemSetting::getValue('social_facebook', ''),
                'social_twitter' => SystemSetting::getValue('social_twitter', ''),
                'social_instagram' => SystemSetting::getValue('social_instagram', ''),
                'social_linkedin' => SystemSetting::getValue('social_linkedin', ''),
            ];

            return response()->json([
                'success' => true,
                'data' => $brandingSettings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branding settings',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update branding settings
     */
    public function updateBrandingSettings(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_name' => 'nullable|string|max:255',
                'site_logo' => 'nullable|string|max:500',
                'site_favicon' => 'nullable|string|max:500',
                'primary_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
                'secondary_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
                'footer_text' => 'nullable|string|max:1000',
                'contact_email' => 'nullable|email|max:255',
                'support_phone' => 'nullable|string|max:20',
                'social_facebook' => 'nullable|url|max:255',
                'social_twitter' => 'nullable|url|max:255',
                'social_instagram' => 'nullable|url|max:255',
                'social_linkedin' => 'nullable|url|max:255',
            ]);

            DB::transaction(function () use ($validated) {
                foreach ($validated as $key => $value) {
                    if ($value !== null) {
                        SystemSetting::updateOrCreate(
                            ['key' => $key],
                            ['value' => $value, 'updated_at' => now()]
                        );
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Branding settings updated successfully',
                'data' => $validated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branding settings',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
