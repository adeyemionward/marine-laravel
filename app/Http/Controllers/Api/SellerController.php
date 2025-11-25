<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewSellerApplicationAdmin;
use App\Mail\SellerApplicationSubmitted;
use App\Models\SellerProfile;
use App\Models\SellerApplication;
use App\Models\SellerReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
class SellerController extends Controller
{
    /**
     * Get all verified sellers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SellerProfile::with(['user', 'userProfile']);

            // Optional: Filter by verification status (uncomment to show only verified sellers)
            // ->verified();

            // Filter by verified status if requested
            // if ($request->boolean('verified_only')) {
            //     $query->verified();
            // }

            // Filter by specialty
            if ($request->filled('specialty')) {
                $query->bySpecialty($request->specialty);
            }

            // Filter by location
            if ($request->filled('location')) {
                $query->byLocation($request->location);
            }

            // Filter by rating
            if ($request->filled('min_rating')) {
                $query->where('rating', '>=', $request->min_rating);
            }

            // Sort options
            switch ($request->get('sort', 'rating')) {
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'reviews':
                    $query->orderBy('review_count', 'desc');
                    break;
                case 'listings':
                    $query->orderBy('total_listings', 'desc');
                    break;
                case 'response':
                    $query->orderBy('avg_response_minutes', 'asc');
                    break;
                case 'featured':
                    $query->featured();
                    break;
                default:
                    $query->orderBy('rating', 'desc');
            }

            $sellers = $query->paginate($request->get('per_page', 12));

            return response()->json([
                'success' => true,
                'data' => $sellers->items(),
                'meta' => [
                    'current_page' => $sellers->currentPage(),
                    'per_page' => $sellers->perPage(),
                    'total' => $sellers->total(),
                    'last_page' => $sellers->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch verified sellers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get featured sellers
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = min(12, max(1, (int) $request->get('limit', 8)));

            $sellers = SellerProfile::with(['user', 'userProfile'])
                ->verified()
                ->featured()
                ->limit($limit)
                ->get();

            // Return empty array if no sellers in database - let frontend handle empty state
            if ($sellers->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'count' => 0,
                        'limit' => $limit,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $sellers,
                'meta' => [
                    'count' => $sellers->count(),
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured sellers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller profile
     */
    public function show($id): JsonResponse
    {
        try {
            $seller = SellerProfile::with(['user', 'userProfile', 'reviews' => function ($query) {
                $query->with('reviewer')->latest()->limit(10);
            }])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $seller,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seller not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get seller listings
     */
    public function listings($id, Request $request): JsonResponse
    {
        try {
            $seller = SellerProfile::findOrFail($id);

            $listings = $seller->listings()
                ->where('status', 'active')
                ->with(['category'])
                ->when($request->filled('category'), function ($query) use ($request) {
                    $query->where('category_id', $request->category);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 12));

            return response()->json([
                'success' => true,
                'data' => $listings->items(),
                'meta' => [
                    'current_page' => $listings->currentPage(),
                    'per_page' => $listings->perPage(),
                    'total' => $listings->total(),
                    'last_page' => $listings->lastPage(),
                ],
                'seller' => [
                    'id' => $seller->id,
                    'name' => $seller->user->name,
                    'business_name' => $seller->business_name,
                    'rating' => $seller->rating,
                    'review_count' => $seller->review_count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch seller listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller statistics
     */
    public function stats($id): JsonResponse
    {
        try {
            $seller = SellerProfile::with('reviews')->findOrFail($id);

            $stats = [
                'overview' => [
                    'rating' => $seller->rating,
                    'total_reviews' => $seller->review_count,
                    'total_listings' => $seller->total_listings,
                    'years_active' => $seller->years_active,
                    'response_time' => $seller->response_time,
                    'specialties' => $seller->specialties,
                ],
                'ratings_breakdown' => [
                    '5_star' => $seller->reviews()->where('rating', 5)->count(),
                    '4_star' => $seller->reviews()->where('rating', 4)->count(),
                    '3_star' => $seller->reviews()->where('rating', 3)->count(),
                    '2_star' => $seller->reviews()->where('rating', 2)->count(),
                    '1_star' => $seller->reviews()->where('rating', 1)->count(),
                ],
                'recent_activity' => [
                    'reviews_this_month' => $seller->reviews()->recent(30)->count(),
                    'avg_monthly_listings' => $seller->listings()->count() / max($seller->years_active * 12, 1),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch seller statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller reviews
     */
    public function reviews($id, Request $request): JsonResponse
    {
        try {
            $seller = SellerProfile::findOrFail($id);

            $reviews = $seller->reviews()
                ->with(['reviewer', 'listing'])
                ->when($request->filled('rating'), function ($query) use ($request) {
                    $query->where('rating', $request->rating);
                })
                ->when($request->boolean('verified_only'), function ($query) {
                    $query->verifiedPurchases();
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $reviews->items(),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'last_page' => $reviews->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch seller reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply for seller verification
     */
    public function apply(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'business_name' => 'required|string|max:255',
                'business_description' => 'required|string|max:1000',
                'business_registration_number' => 'nullable|string|max:100',
                'tax_identification_number' => 'nullable|string|max:100',
                'specialties' => 'required|array|min:1',
                'specialties.*' => 'string|max:100',
                'years_experience' => 'required|integer|min:0|max:50',
                'previous_platforms' => 'nullable|string|max:500',
                'motivation' => 'nullable|string|max:1000',
                'business_documents' => 'nullable|array',
                'business_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            ]);

            $user = $request->user();

            // Check if user already has an active application
            $existingApplication = SellerApplication::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'under_review'])
                ->first();

            if ($existingApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending seller application',
                ], 422);
            }

            // Handle document uploads
            $documentPaths = [];
            if ($request->hasFile('business_documents')) {
                foreach ($request->file('business_documents') as $document) {
                    $path = $document->store('seller-applications', 'public');
                    $documentPaths[] = $path;
                }
            }

            $application = SellerApplication::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'business_description' => $request->business_description,
                'business_registration_number' => $request->business_registration_number,
                'tax_identification_number' => $request->tax_identification_number,
                'business_documents' => $documentPaths,
                'specialties' => $request->specialties,
                'years_experience' => $request->years_experience,
                'previous_platforms' => $request->previous_platforms,
                'motivation' => $request->motivation,
            ]);

            // Send email to user (seller applicant)
            Mail::to($user->email)->send(new SellerApplicationSubmitted($application));

            // Send email to admin
            Mail::to('info@marine.africa')->send(new NewSellerApplicationAdmin($application));

            return response()->json([
                'success' => true,
                'message' => 'Seller application submitted successfully',
                'data' => $application,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit seller application',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller application status
     */
    public function applicationStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $application = SellerApplication::where('user_id', $user->id)
                ->latest()
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => true,
                    'data' => ['status' => 'not_applied'],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $application->status,
                    'submitted_at' => $application->created_at,
                    'reviewed_at' => $application->reviewed_at,
                    'admin_notes' => $application->admin_notes,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch application status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller specialties list
     */
    public function specialties(): JsonResponse
    {
        try {
            $specialties = [
                'Boats',
                'Engines',
                'Electronics',
                'Navigation',
                'Safety Equipment',
                'Communication',
                'Fishing Equipment',
                'Parts & Accessories',
                'Life Jackets',
                'Emergency Gear',
                'Marine Hardware',
                'Propellers',
                'Anchoring',
                'Maintenance',
                'Fuel Systems',
            ];

            return response()->json([
                'success' => true,
                'data' => $specialties,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch specialties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user has a seller profile
            $sellerProfile = SellerProfile::where('user_id', $user->id)->first();

            if (!$sellerProfile) {
                // User is not a seller yet, return application status
                $application = SellerApplication::where('user_id', $user->id)
                    ->latest()
                    ->first();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_seller' => $user->isSeller(),
                        'application_status' => $application ? [
                            'status' => $application->status,
                            'submitted_at' => $application->created_at,
                            'reviewed_at' => $application->reviewed_at,
                            'admin_notes' => $application->admin_notes,
                        ] : null,
                    ],
                ]);
            }

            // Get comprehensive dashboard data
            $currentMonth = now()->startOfMonth();
            $lastMonth = now()->subMonth()->startOfMonth();

            // Get listings statistics
            $activeListings = $sellerProfile->listings()
                ->where('status', 'active')
                ->count();

            $pendingListings = $sellerProfile->listings()
                ->where('status', 'pending')
                ->count();

            $soldListings = $sellerProfile->listings()
                ->where('status', 'sold')
                ->count();

            // Get recent listings
            $recentListings = $sellerProfile->listings()
                ->with(['category', 'images'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get views and inquiries
            $totalViews = $sellerProfile->listings()
                ->sum('views');

            $monthlyViews = $sellerProfile->listings()
                ->join('equipment_views', 'equipment.id', '=', 'equipment_views.equipment_id')
                ->where('equipment_views.created_at', '>=', $currentMonth)
                ->count();

            // Get recent reviews
            $recentReviews = $sellerProfile->reviews()
                ->with(['reviewer', 'listing'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Get messages/inquiries count
            $unreadMessages = DB::table('conversations')
                ->join('messages', 'conversations.id', '=', 'messages.conversation_id')
                ->where('conversations.seller_id', $user->id)
                ->where('messages.is_read', false)
                ->where('messages.sender_id', '!=', $user->id)
                ->count();

            // Calculate growth metrics
            $currentMonthListings = $sellerProfile->listings()
                ->where('created_at', '>=', $currentMonth)
                ->count();

            $lastMonthListings = $sellerProfile->listings()
                ->whereBetween('created_at', [$lastMonth, $currentMonth])
                ->count();

            $listingsGrowth = $lastMonthListings > 0
                ? (($currentMonthListings - $lastMonthListings) / $lastMonthListings) * 100
                : 0;

            // Get performance metrics
            $avgResponseTime = DB::table('conversations')
                ->join('messages', 'conversations.id', '=', 'messages.conversation_id')
                ->where('conversations.seller_id', $user->id)
                ->where('messages.sender_id', $user->id)
                ->whereNotNull('messages.response_time_minutes')
                ->avg('messages.response_time_minutes');

            return response()->json([
                'success' => true,
                'data' => [
                    'is_seller' => $user->isSeller(),
                    'seller_profile' => [
                        'id' => $sellerProfile->id,
                        'business_name' => $sellerProfile->business_name,
                        'rating' => $sellerProfile->rating,
                        'review_count' => $sellerProfile->review_count,
                        'is_featured' => $sellerProfile->is_featured,
                        'is_verified' => $sellerProfile->is_verified,
                        'specialties' => $sellerProfile->specialties,
                    ],
                    'statistics' => [
                        'listings' => [
                            'active' => $activeListings,
                            'pending' => $pendingListings,
                            'sold' => $soldListings,
                            'total' => $activeListings + $pendingListings + $soldListings,
                        ],
                        'views' => [
                            'total' => $totalViews,
                            'monthly' => $monthlyViews,
                        ],
                        'messages' => [
                            'unread' => $unreadMessages,
                        ],
                        'performance' => [
                            'avg_response_time' => $avgResponseTime ? round($avgResponseTime) : null,
                            'listings_growth' => round($listingsGrowth, 1),
                        ],
                    ],
                    'recent_listings' => $recentListings,
                    'recent_reviews' => $recentReviews,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller dashboard stats (for authenticated seller)
     */
    public function getSellerStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $sellerProfile = SellerProfile::where('user_id', $user->id)->firstOrFail();

            $period = $request->get('period', '30'); // days
            $startDate = now()->subDays((int)$period);

            // Listings stats
            $totalListings = $sellerProfile->listings()->count();
            $activeListings = $sellerProfile->listings()->where('status', 'active')->count();
            $soldListings = $sellerProfile->listings()->where('status', 'sold')->count();
            $totalViews = $sellerProfile->listings()->sum('view_count');
            $totalInquiries = $sellerProfile->listings()->sum('inquiry_count');

            // Revenue stats (if orders exist)
            $totalRevenue = DB::table('orders')
                ->where('seller_id', $user->id)
                ->where('payment_status', 'completed')
                ->sum('total_amount');

            $periodRevenue = DB::table('orders')
                ->where('seller_id', $user->id)
                ->where('payment_status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('total_amount');

            // Reviews stats
            $averageRating = $sellerProfile->rating;
            $totalReviews = $sellerProfile->review_count;
            $recentReviews = $sellerProfile->reviews()
                ->where('created_at', '>=', $startDate)
                ->count();

            // Performance metrics
            $conversionRate = $totalInquiries > 0
                ? round(($soldListings / $totalInquiries) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'listings' => [
                        'total' => $totalListings,
                        'active' => $activeListings,
                        'sold' => $soldListings,
                        'pending' => $totalListings - $activeListings - $soldListings,
                    ],
                    'engagement' => [
                        'total_views' => $totalViews,
                        'total_inquiries' => $totalInquiries,
                        'average_views_per_listing' => $totalListings > 0 ? round($totalViews / $totalListings, 1) : 0,
                    ],
                    'revenue' => [
                        'total' => $totalRevenue,
                        'period' => $periodRevenue,
                        'currency' => 'NGN',
                    ],
                    'reviews' => [
                        'average_rating' => $averageRating,
                        'total_reviews' => $totalReviews,
                        'recent_reviews' => $recentReviews,
                    ],
                    'performance' => [
                        'conversion_rate' => $conversionRate,
                        'response_time' => $sellerProfile->response_time,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch seller stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller's reviews (for authenticated seller)
     */
    public function getSellerReviews(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $sellerProfile = SellerProfile::where('user_id', $user->id)->firstOrFail();

            $reviews = $sellerProfile->reviews()
                ->with(['reviewer', 'listing'])
                ->when($request->filled('rating'), function ($query) use ($request) {
                    $query->where('rating', $request->rating);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            $ratingsBreakdown = [
                5 => $sellerProfile->reviews()->where('rating', 5)->count(),
                4 => $sellerProfile->reviews()->where('rating', 4)->count(),
                3 => $sellerProfile->reviews()->where('rating', 3)->count(),
                2 => $sellerProfile->reviews()->where('rating', 2)->count(),
                1 => $sellerProfile->reviews()->where('rating', 1)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $reviews->items(),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'last_page' => $reviews->lastPage(),
                ],
                'summary' => [
                    'average_rating' => $sellerProfile->rating,
                    'total_reviews' => $sellerProfile->review_count,
                    'ratings_breakdown' => $ratingsBreakdown,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get seller's favorite listings
     */
    public function getFavorites(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $favorites = DB::table('user_favorites')
                ->join('equipment_listings', 'user_favorites.listing_id', '=', 'equipment_listings.id')
                ->join('equipment_categories', 'equipment_listings.category_id', '=', 'equipment_categories.id')
                ->where('user_favorites.user_profile_id', $user->id)
                ->select(
                    'equipment_listings.*',
                    'equipment_categories.name as category_name',
                    'user_favorites.created_at as favorited_at'
                )
                ->orderBy('user_favorites.created_at', 'desc')
                ->paginate($request->get('per_page', 12));

            return response()->json([
                'success' => true,
                'data' => $favorites->items(),
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

    /**
     * Get seller's activity log
     */
    public function getActivityLog(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $activities = \App\Models\SellerActivityLog::forUser($user->id)
                ->with('related')
                ->when($request->filled('type'), function ($query) use ($request) {
                    $query->byType($request->type);
                })
                ->when($request->filled('days'), function ($query) use ($request) {
                    $query->recent((int)$request->days);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            // Activity type summary
            $activityTypes = \App\Models\SellerActivityLog::forUser($user->id)
                ->select('action_type', DB::raw('count(*) as count'))
                ->groupBy('action_type')
                ->get()
                ->pluck('count', 'action_type');

            return response()->json([
                'success' => true,
                'data' => $activities->items(),
                'meta' => [
                    'current_page' => $activities->currentPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                    'last_page' => $activities->lastPage(),
                ],
                'summary' => [
                    'activity_types' => $activityTypes,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity log',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reply to a review (seller can respond to reviews)
     */
    public function replyToReview(Request $request, $reviewId): JsonResponse
    {
        try {
            $request->validate([
                'reply' => 'required|string|max:1000',
            ]);

            $user = $request->user();
            $review = SellerReview::findOrFail($reviewId);

            // Verify this review belongs to the authenticated seller
            $sellerProfile = SellerProfile::where('user_id', $user->id)->firstOrFail();
            if ($review->seller_id !== $sellerProfile->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reply to this review',
                ], 403);
            }

            $review->update([
                'seller_reply' => $request->reply,
                'seller_replied_at' => now(),
            ]);

            // Log activity
            \App\Models\SellerActivityLog::log(
                $user->id,
                'review_replied',
                'Replied to a review',
                $review,
                ['rating' => $review->rating]
            );

            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully',
                'data' => $review->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to post reply',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // ADMIN SELLER MANAGEMENT ENDPOINTS
    // ============================================

    /**
     * Update seller profile (Admin only)
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $seller = SellerProfile::findOrFail($id);

            $validated = $request->validate([
                'business_name' => 'sometimes|string|max:255',
                'location' => 'sometimes|string|max:255',
                'years_active' => 'sometimes|integer|min:0',
                'rating' => 'sometimes|numeric|min:0|max:5',
                'bio' => 'sometimes|nullable|string',
                'is_verified' => 'sometimes|boolean',
                'is_featured' => 'sometimes|boolean',
            ]);

            $seller->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Seller updated successfully',
                'data' => $seller->fresh(['user', 'userProfile']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update seller',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete seller profile (Admin only)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $seller = SellerProfile::findOrFail($id);

            // Optionally handle cascading deletes or soft delete
            $seller->delete();

            return response()->json([
                'success' => true,
                'message' => 'Seller deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete seller',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle seller verification status (Admin only)
     */
    public function toggleVerification($id): JsonResponse
    {
        try {
            $seller = SellerProfile::findOrFail($id);

            $seller->update([
                'is_verified' => !$seller->is_verified,
            ]);

            return response()->json([
                'success' => true,
                'message' => $seller->is_verified ? 'Seller verified successfully' : 'Seller unverified successfully',
                'data' => $seller->fresh(['user', 'userProfile']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle verification status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle seller featured status (Admin only)
     */
    public function toggleFeatured($id): JsonResponse
    {
        try {
            $seller = SellerProfile::findOrFail($id);

            $seller->update([
                'is_featured' => !$seller->is_featured,
            ]);

            return response()->json([
                'success' => true,
                'message' => $seller->is_featured ? 'Seller added to featured list' : 'Seller removed from featured list',
                'data' => $seller->fresh(['user', 'userProfile']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
