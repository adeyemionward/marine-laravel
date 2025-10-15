<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\EquipmentListingResource;
use App\Models\EquipmentListing;
use App\Models\UserFavorite;
use App\Models\Subscription;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
            $userId = Auth::id();

            $favorites = UserFavorite::where('user_id', $userId)
                ->with(['listing'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $listings = $favorites->getCollection()->map(function ($favorite) {
                return $favorite->listing;
            })->filter(); // Remove nulls in case listing was deleted

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
            \Log::error('Favorites Fetch Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id()
            ]);

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
            $user = Auth::user();
            $userId = $user->profile ? $user->profile->id : null;

            if (!$userId) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No active subscription found',
                ]);
            }

            $subscription = Subscription::where('user_id', $userId)
                ->with('plan')
                ->active()
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

    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            // Get current date ranges
            $today = now()->startOfDay();
            $lastWeek = now()->subWeek();
            $lastMonth = now()->subMonth();

            // Active listings count
            $activeListings = EquipmentListing::where('seller_id', $userId)
                ->where('status', 'active')
                ->count();

            $listingsLastMonth = EquipmentListing::where('seller_id', $userId)
                ->where('status', 'active')
                ->where('created_at', '>=', $lastMonth)
                ->count();

            $allListings = EquipmentListing::where('seller_id', $userId)
                ->where('status', 'active')
                ->count();

            $listingsChange = $allListings > 0 && $listingsLastMonth > 0
                ? round(($listingsLastMonth / max($allListings - $listingsLastMonth, 1)) * 100, 1)
                : 0;

            // Total views from all user listings
            $totalViews = EquipmentListing::where('seller_id', $userId)
                ->sum('view_count') ?? 0;

            $viewsLastMonth = EquipmentListing::where('seller_id', $userId)
                ->where('updated_at', '>=', $lastMonth)
                ->sum('view_count') ?? 0;

            $viewsChange = $totalViews > 0 && $viewsLastMonth > 0
                ? round(($viewsLastMonth / max($totalViews - $viewsLastMonth, 1)) * 100, 1)
                : 0;

            // Message/inquiry count (conversations where user is involved)
            $totalInquiries = Conversation::where(function($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('seller_id', $userId);
            })->count();

            $recentInquiries = Conversation::where(function($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('seller_id', $userId);
            })->where('created_at', '>=', $lastMonth)->count();

            $inquiriesChange = $totalInquiries > 0 && $recentInquiries > 0
                ? round(($recentInquiries / max($totalInquiries - $recentInquiries, 1)) * 100, 1)
                : 0;

            // Favorites count - user_favorites uses user_id which references user_profiles.id
            $userProfile = $user->profile;
            $profileId = $userProfile ? $userProfile->id : null;

            $savedItems = $profileId ? UserFavorite::where('user_id', $profileId)->count() : 0;

            $recentFavorites = $profileId ? UserFavorite::where('user_id', $profileId)
                ->where('created_at', '>=', $lastMonth)
                ->count() : 0;

            $favoritesChange = $savedItems > 0 && $recentFavorites > 0
                ? round(($recentFavorites / max($savedItems - $recentFavorites, 1)) * 100, 1)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'activeListings' => $activeListings,
                    'listingsChange' => $listingsChange,
                    'totalViews' => $totalViews,
                    'viewsChange' => $viewsChange,
                    'recentInquiries' => $recentInquiries,
                    'inquiriesChange' => $inquiriesChange,
                    'savedItems' => $savedItems,
                    'favoritesChange' => $favoritesChange,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function activity(): JsonResponse
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            // Get recent activities
            $activities = [];

            // Recent listings
            $recentListings = EquipmentListing::where('seller_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'status', 'created_at', 'view_count']);

            foreach ($recentListings as $listing) {
                $activities[] = [
                    'type' => 'listing_created',
                    'title' => 'Created listing',
                    'description' => $listing->title,
                    'date' => $listing->created_at,
                    'status' => $listing->status,
                    'metadata' => [
                        'listing_id' => $listing->id,
                        'views' => $listing->view_count,
                    ]
                ];
            }

            // Recent favorites - use profile id
            $userProfile = $user->profile;
            $profileId = $userProfile ? $userProfile->id : null;

            $recentFavorites = $profileId ? UserFavorite::where('user_id', $profileId)
                ->with('listing:id,title')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get() : collect();

            foreach ($recentFavorites as $favorite) {
                $activities[] = [
                    'type' => 'favorite_added',
                    'title' => 'Added to favorites',
                    'description' => $favorite->listing->title ?? 'Unknown listing',
                    'date' => $favorite->created_at,
                    'metadata' => [
                        'listing_id' => $favorite->listing_id,
                    ]
                ];
            }

            // Recent messages - use profile id
            $profileId = $userProfile ? $userProfile->id : null;

            $recentMessages = $profileId ? Message::whereHas('conversation', function($q) use ($profileId) {
                $q->where('buyer_id', $profileId)
                  ->orWhere('seller_id', $profileId);
            })
            ->where('sender_id', '!=', $profileId) // Messages from others to user
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'content', 'created_at']) : collect();

            foreach ($recentMessages as $message) {
                $activities[] = [
                    'type' => 'message_received',
                    'title' => 'Received message',
                    'description' => substr($message->content, 0, 100) . (strlen($message->content) > 100 ? '...' : ''),
                    'date' => $message->created_at,
                    'metadata' => [
                        'message_id' => $message->id,
                    ]
                ];
            }

            // Sort all activities by date
            usort($activities, function($a, $b) {
                return $b['date'] <=> $a['date'];
            });

            // Take only the most recent 20
            $activities = array_slice($activities, 0, 20);

            return response()->json([
                'success' => true,
                'data' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user activity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dashboardOverview(): JsonResponse
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            // Get current date ranges for stats calculation
            $today = now()->startOfDay();
            $lastWeek = now()->subWeek();
            $lastMonth = now()->subMonth();

            // Calculate stats directly instead of calling other methods
            // Active listings count
            $activeListings = EquipmentListing::where('seller_id', $userId)
                ->where('status', 'active')
                ->count();

            $listingsLastMonth = EquipmentListing::where('seller_id', $userId)
                ->where('status', 'active')
                ->where('created_at', '>=', $lastMonth)
                ->count();

            $allListings = EquipmentListing::where('seller_id', $userId)
                ->where('status', 'active')
                ->count();

            $listingsChange = $allListings > 0 && $listingsLastMonth > 0
                ? round(($listingsLastMonth / max($allListings - $listingsLastMonth, 1)) * 100, 1)
                : 0;

            // Total views from all user listings
            $totalViews = EquipmentListing::where('seller_id', $userId)
                ->sum('view_count') ?? 0;

            $viewsLastMonth = EquipmentListing::where('seller_id', $userId)
                ->where('updated_at', '>=', $lastMonth)
                ->sum('view_count') ?? 0;

            $viewsChange = $totalViews > 0 && $viewsLastMonth > 0
                ? round(($viewsLastMonth / max($totalViews - $viewsLastMonth, 1)) * 100, 1)
                : 0;

            // Message/inquiry count (conversations where user is involved)
            $totalInquiries = Conversation::where(function($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('seller_id', $userId);
            })->count();

            $recentInquiries = Conversation::where(function($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('seller_id', $userId);
            })->where('created_at', '>=', $lastMonth)->count();

            $inquiriesChange = $totalInquiries > 0 && $recentInquiries > 0
                ? round(($recentInquiries / max($totalInquiries - $recentInquiries, 1)) * 100, 1)
                : 0;

            // Favorites count - user_favorites uses user_id which references user_profiles.id
            $userProfile = $user->profile;
            $profileId = $userProfile ? $userProfile->id : null;

            $savedItems = $profileId ? UserFavorite::where('user_id', $profileId)->count() : 0;

            $recentFavorites = $profileId ? UserFavorite::where('user_id', $profileId)
                ->where('created_at', '>=', $lastMonth)
                ->count() : 0;

            $favoritesChange = $savedItems > 0 && $recentFavorites > 0
                ? round(($recentFavorites / max($savedItems - $recentFavorites, 1)) * 100, 1)
                : 0;

            $stats = [
                'activeListings' => $activeListings,
                'listingsChange' => $listingsChange,
                'totalViews' => $totalViews,
                'viewsChange' => $viewsChange,
                'recentInquiries' => $recentInquiries,
                'inquiriesChange' => $inquiriesChange,
                'savedItems' => $savedItems,
                'favoritesChange' => $favoritesChange,
            ];

            // Get recent activities
            $activities = [];

            // Recent listings
            $recentListingsData = EquipmentListing::where('seller_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'status', 'created_at', 'view_count']);

            foreach ($recentListingsData as $listing) {
                $activities[] = [
                    'type' => 'listing_created',
                    'title' => 'Created listing',
                    'description' => $listing->title,
                    'date' => $listing->created_at,
                    'status' => $listing->status,
                    'metadata' => [
                        'listing_id' => $listing->id,
                        'views' => $listing->view_count,
                    ]
                ];
            }

            // Recent favorites - use profile id
            $userProfile = $user->profile;
            $profileId = $userProfile ? $userProfile->id : null;

            $recentFavoritesData = $profileId ? UserFavorite::where('user_id', $profileId)
                ->with('listing:id,title')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get() : collect();

            foreach ($recentFavoritesData as $favorite) {
                if ($favorite->listing) {
                    $activities[] = [
                        'type' => 'favorite_added',
                        'title' => 'Added to favorites',
                        'description' => $favorite->listing->title ?? 'Unknown listing',
                        'date' => $favorite->created_at,
                        'metadata' => [
                            'listing_id' => $favorite->listing_id,
                        ]
                    ];
                }
            }

            // Sort all activities by date
            usort($activities, function($a, $b) {
                return $b['date'] <=> $a['date'];
            });

            // Take only the most recent 10
            $recentActivity = array_slice($activities, 0, 10);

            // Get subscription info
            $subscription = null;
            $activeSubscription = Subscription::where('user_id', $userId)
                ->with('plan')
                ->where('status', 'active')
                ->first();

            if ($activeSubscription && $activeSubscription->plan) {
                $subscription = [
                    'id' => $activeSubscription->id,
                    'status' => $activeSubscription->status,
                    'started_at' => $activeSubscription->started_at,
                    'expires_at' => $activeSubscription->expires_at,
                    'auto_renew' => $activeSubscription->auto_renew,
                    'plan' => [
                        'id' => $activeSubscription->plan->id,
                        'name' => $activeSubscription->plan->name,
                        'tier' => $activeSubscription->plan->tier,
                        'price' => $activeSubscription->plan->price,
                        'billing_cycle' => $activeSubscription->plan->billing_cycle,
                        'features' => $activeSubscription->plan->features,
                        'max_listings' => $activeSubscription->plan->max_listings,
                    ],
                    'days_remaining' => $activeSubscription->expires_at ?
                        max(0, $activeSubscription->expires_at->diffInDays(now())) : null,
                ];
            }

            // Get recent listings with category
            $recentListings = EquipmentListing::where('seller_id', $userId)
                ->with('category')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'price', 'status', 'created_at', 'category_id', 'view_count']);

            // Get recent messages count - check if Message model exists
            $unreadMessages = 0;
            if (class_exists('App\Models\Message') && class_exists('App\Models\Conversation')) {
                // Messages uses sender_id which references user_profiles.id
                $profileId = $user->profile ? $user->profile->id : null;

                if ($profileId) {
                    $unreadMessages = Message::whereHas('conversation', function($q) use ($profileId) {
                        $q->where('buyer_id', $profileId)
                          ->orWhere('seller_id', $profileId);
                    })
                    ->where('sender_id', '!=', $profileId)
                    ->where('read_at', null)
                    ->count();
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->getRoleName(),
                        'profile' => $user->profile,
                    ],
                    'stats' => $stats,
                    'subscription' => $subscription,
                    'recent_activity' => $recentActivity,
                    'recent_listings' => $recentListings,
                    'unread_messages' => $unreadMessages,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard overview',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




}
