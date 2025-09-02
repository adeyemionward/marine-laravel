<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\SellerApplication;
use App\Models\SellerReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerController extends Controller
{
    /**
     * Get all verified sellers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SellerProfile::with(['user', 'userProfile'])
                ->verified();

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

            // Return sample data if no sellers in database
            if ($sellers->isEmpty()) {
                $sampleSellers = [
                    [
                        'id' => 1,
                        'user' => ['id' => 1, 'name' => 'Marine Pro Lagos'],
                        'business_name' => 'Marine Pro Equipment Ltd',
                        'business_description' => 'Leading marine equipment supplier in Lagos with over 8 years of experience serving the Nigerian maritime industry.',
                        'location' => 'Lagos, Nigeria',
                        'rating' => 4.9,
                        'review_count' => 156,
                        'years_active' => 8,
                        'specialties' => ['Engines', 'Boats', 'Electronics'],
                        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
                        'total_listings' => 45,
                        'response_time' => '< 2 hours',
                        'verification_status' => 'approved',
                        'is_featured' => true,
                    ],
                    [
                        'id' => 2,
                        'user' => ['id' => 2, 'name' => 'Rivers Marine Center'],
                        'business_name' => 'Rivers Marine Solutions',
                        'business_description' => 'Trusted marine equipment dealer specializing in fishing boats and commercial vessels for the Niger Delta region.',
                        'location' => 'Port Harcourt, Rivers',
                        'rating' => 4.8,
                        'review_count' => 203,
                        'years_active' => 12,
                        'specialties' => ['Boats', 'Safety Equipment', 'Parts'],
                        'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150',
                        'total_listings' => 67,
                        'response_time' => '< 1 hour',
                        'verification_status' => 'approved',
                        'is_featured' => true,
                    ],
                    [
                        'id' => 3,
                        'user' => ['id' => 3, 'name' => 'Tech Marine Solutions'],
                        'business_name' => 'Tech Marine Electronics',
                        'business_description' => 'Specialized in marine electronics and navigation systems with certified technicians and warranty support.',
                        'location' => 'Abuja, FCT',
                        'rating' => 4.7,
                        'review_count' => 89,
                        'years_active' => 5,
                        'specialties' => ['Electronics', 'Navigation', 'Communication'],
                        'avatar' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=150',
                        'total_listings' => 32,
                        'response_time' => '< 3 hours',
                        'verification_status' => 'approved',
                        'is_featured' => true,
                    ],
                    [
                        'id' => 4,
                        'user' => ['id' => 4, 'name' => 'Safety First Marine'],
                        'business_name' => 'Safety First Marine Ltd',
                        'business_description' => 'Dedicated to marine safety with comprehensive range of certified safety equipment and emergency gear.',
                        'location' => 'Calabar, Cross River',
                        'rating' => 4.9,
                        'review_count' => 134,
                        'years_active' => 10,
                        'specialties' => ['Safety Equipment', 'Life Jackets', 'Emergency Gear'],
                        'avatar' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150',
                        'total_listings' => 28,
                        'response_time' => '< 1 hour',
                        'verification_status' => 'approved',
                        'is_featured' => true,
                    ],
                ];

                return response()->json([
                    'success' => true,
                    'data' => array_slice($sampleSellers, 0, $limit),
                    'meta' => [
                        'count' => min(count($sampleSellers), $limit),
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
                ->verified()
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
            $seller = SellerProfile::verified()->findOrFail($id);
            
            $listings = $seller->listings()
                ->where('status', 'active')
                ->with(['category', 'images'])
                ->when($request->filled('category'), function ($query) use ($request) {
                    $query->where('category', $request->category);
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
            $seller = SellerProfile::with('reviews')->verified()->findOrFail($id);
            
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
            $seller = SellerProfile::verified()->findOrFail($id);
            
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
}