<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentListing;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\SystemSetting;
use App\Models\SellerApplication;
use App\Http\Resources\EquipmentListingResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $listing = EquipmentListing::findOrFail($id);
            
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

            $listing = EquipmentListing::findOrFail($id);
            
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
            $listing = EquipmentListing::findOrFail($id);
            
            $listing->update([
                'is_featured' => !$listing->is_featured,
            ]);

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
                        $includes[] = 'subscriptions'; // Map to actual relationship name
                    } elseif ($include === 'subscriptions') {
                        $includes[] = 'subscriptions';
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
                'plan_id' => 'nullable|string',
                'admin_notes' => 'nullable|string',
                'verification_level' => 'nullable|string|in:basic,premium,enterprise',
                'welcome_message' => 'nullable|boolean',
                'send_email' => 'nullable|boolean',
                'grant_immediate_access' => 'nullable|boolean',
            ]);

            $application = SellerApplication::findOrFail($validated['application_id']);
            $application->approve(auth()->user(), $validated['admin_notes'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Seller application approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve application',
                'error' => $e->getMessage(),
            ], 500);
        }
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
}