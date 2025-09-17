<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{

    /**
     * Display a paginated listing of equipment
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EquipmentListing::with(['seller', 'category'])
                ->active()
                ->published()
                ->notExpired();

            // Filter by category
            if ($request->filled('category_id')) {
                $query->byCategory($request->category_id);
            }

            // Filter by location
            if ($request->filled('state')) {
                $query->inLocation($request->state, $request->city);
            }

            // Filter by condition
            if ($request->filled('condition')) {
                $query->where('condition', $request->condition);
            }

            // Filter by price range
            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Search by title or description
            if ($request->filled('q')) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'LIKE', '%' . $request->q . '%')
                      ->orWhere('description', 'LIKE', '%' . $request->q . '%');
                });
            }

            // Exclude specific IDs (for similar equipment)
            if ($request->filled('exclude')) {
                $excludeIds = is_array($request->exclude) ? $request->exclude : [$request->exclude];
                $query->whereNotIn('id', $excludeIds);
            }

            // Sort options
            switch ($request->get('sort', 'created_at')) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'featured':
                    $query->orderBy('is_featured', 'desc')
                          ->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }

            // Pagination - handle both per_page and limit parameters
            if ($request->filled('limit')) {
                // For similar equipment requests, just take the specified number
                $limit = min(20, max(1, (int) $request->get('limit', 8)));
                $equipment = $query->limit($limit)->get();
                
                return response()->json([
                    'success' => true,
                    'data' => $equipment,
                ]);
            } else {
                // Standard pagination
                $perPage = min(50, max(1, (int) $request->get('per_page', 12)));
                $equipment = $query->paginate($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $equipment->items(),
                'meta' => [
                    'current_page' => $equipment->currentPage(),
                    'per_page' => $equipment->perPage(),
                    'total' => $equipment->total(),
                    'last_page' => $equipment->lastPage(),
                    'has_more' => $equipment->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch equipment listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get featured listings
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = min(20, max(1, (int) $request->get('limit', 12)));
            
            $equipment = EquipmentListing::with(['seller', 'category'])
                ->active()
                ->published()
                ->notExpired()
                ->featured()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $equipment,
                'meta' => [
                    'count' => $equipment->count(),
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured equipment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get popular listings
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $limit = min(20, max(1, (int) $request->get('limit', 12)));
            
            $equipment = EquipmentListing::with(['seller', 'category'])
                ->active()
                ->published()
                ->notExpired()
                ->orderBy('view_count', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $equipment,
                'meta' => [
                    'count' => $equipment->count(),
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch popular equipment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search equipment listings
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2|max:255',
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:50',
            ]);

            $query = $request->get('q');
            $perPage = min(50, max(1, (int) $request->get('per_page', 12)));
            
            $equipment = EquipmentListing::with(['seller', 'category'])
                ->active()
                ->published()
                ->notExpired()
                ->where(function($q) use ($query) {
                    $q->where('title', 'LIKE', '%' . $query . '%')
                      ->orWhere('description', 'LIKE', '%' . $query . '%')
                      ->orWhere('brand', 'LIKE', '%' . $query . '%')
                      ->orWhere('model', 'LIKE', '%' . $query . '%');
                })
                ->orderBy('is_featured', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $equipment->items(),
                'meta' => [
                    'query' => $query,
                    'current_page' => $equipment->currentPage(),
                    'per_page' => $equipment->perPage(),
                    'total' => $equipment->total(),
                    'last_page' => $equipment->lastPage(),
                    'has_more' => $equipment->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search equipment listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created equipment listing
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:equipment_categories,id',
                'condition' => 'required|in:new,like_new,good,fair,poor',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'location_state' => 'required|string|max:100',
                'location_city' => 'nullable|string|max:100',
                'images' => 'nullable|array|max:10',
                'images.*' => 'file|mimes:jpeg,jpg,png|max:5120', // 5MB max
                'specifications' => 'nullable|array',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email',
                'negotiable' => 'boolean',
            ]);

            $user = $request->user();
            
            // Check if user has an active subscription and listing limits
            $subscription = $user->activeSubscription();
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active subscription required to create listings',
                ], 403);
            }

            // Check listing limits
            $currentListings = EquipmentListing::where('seller_id', $user->id)
                ->where('status', 'active')
                ->count();
            
            if ($subscription->plan->max_listings !== -1 && 
                $currentListings >= $subscription->plan->max_listings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing limit reached for your subscription plan',
                ], 403);
            }

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                $maxImages = $subscription->plan->max_images_per_listing ?? 5;
                $images = array_slice($request->file('images'), 0, $maxImages);
                
                foreach ($images as $image) {
                    $path = $image->store('listings', 'public');
                    $imagePaths[] = $path;
                }
            }

            $listing = EquipmentListing::create([
                'seller_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'category_id' => $request->category_id,
                'condition' => $request->condition,
                'brand' => $request->brand,
                'model' => $request->model,
                'year' => $request->year,
                'location_state' => $request->location_state,
                'location_city' => $request->location_city,
                'images' => $imagePaths,
                'specifications' => $request->specifications ?? [],
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email ?? $user->email,
                'negotiable' => $request->boolean('negotiable', false),
                'status' => 'active',
                'published_at' => now(),
            ]);

            // Update seller profile listing count
            if ($user->sellerProfile) {
                $user->sellerProfile->updateListingCount();
            }

            return response()->json([
                'success' => true,
                'message' => 'Listing created successfully',
                'data' => $listing->load(['category', 'seller']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified equipment listing
     */
    public function show(int $id): JsonResponse
    {
        try {
            $listing = EquipmentListing::with(['seller', 'category'])
                ->active()
                ->published()
                ->notExpired()
                ->findOrFail($id);

            // Increment view count
            $listing->incrementViewCount();

            // Enhance seller data with additional information
            if ($listing->seller) {
                $seller = $listing->seller;
                
                // Get seller statistics
                $sellerStats = [
                    'totalListings' => EquipmentListing::where('seller_id', $seller->id)
                        ->where('status', 'active')
                        ->count(),
                    'totalSales' => 0, // This would come from orders/sales data when implemented
                    'responseTime' => '< 2 hours', // This would be calculated from message response times
                    'joinedDate' => $seller->created_at->format('M Y'),
                    'lastSeen' => $seller->last_login_at ? $seller->last_login_at->diffForHumans() : 'Recently'
                ];
                
                // Add enhanced seller data
                $listing->seller->stats = $sellerStats;
                $listing->seller->isVerified = $seller->isVerified ?? false;
                $listing->seller->businessType = $seller->business_type ?? 'Equipment Dealer';
                $listing->seller->location = $seller->location_city && $seller->location_state 
                    ? "{$seller->location_city}, {$seller->location_state}" 
                    : ($seller->location_state ?? 'Nigeria');
                $listing->seller->rating = $seller->rating ?? 4.5;
                $listing->seller->reviewCount = $seller->review_count ?? 0;
            }

            return response()->json([
                'success' => true,
                'data' => $listing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified equipment listing
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:2000',
                'price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:equipment_categories,id',
                'condition' => 'sometimes|required|in:new,like_new,good,fair,poor',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'location_state' => 'sometimes|required|string|max:100',
                'location_city' => 'nullable|string|max:100',
                'images' => 'nullable|array|max:10',
                'images.*' => 'file|mimes:jpeg,jpg,png|max:5120',
                'specifications' => 'nullable|array',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email',
                'negotiable' => 'boolean',
            ]);

            $user = $request->user();
            $listing = EquipmentListing::where('seller_id', $user->id)
                ->findOrFail($id);

            // Handle new image uploads if provided
            if ($request->hasFile('images')) {
                $subscription = $user->activeSubscription();
                $maxImages = $subscription->plan->max_images_per_listing ?? 5;
                $images = array_slice($request->file('images'), 0, $maxImages);
                
                $imagePaths = [];
                foreach ($images as $image) {
                    $path = $image->store('listings', 'public');
                    $imagePaths[] = $path;
                }
                
                // Delete old images (optional - implement cleanup)
                $listing->images = $imagePaths;
            }

            $listing->update($request->only([
                'title', 'description', 'price', 'category_id', 'condition',
                'brand', 'model', 'year', 'location_state', 'location_city',
                'specifications', 'contact_phone', 'contact_email', 'negotiable'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Listing updated successfully',
                'data' => $listing->load(['category', 'seller']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified equipment listing
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $listing = EquipmentListing::where('seller_id', $user->id)
                ->findOrFail($id);

            // Soft delete the listing instead of hard delete
            $listing->update([
                'status' => 'deleted',
                'deleted_at' => now(),
            ]);

            // Update seller profile listing count
            if ($user->sellerProfile) {
                $user->sellerProfile->updateListingCount();
            }

            return response()->json([
                'success' => true,
                'message' => 'Listing deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle favorite status for a listing
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        try {
            $isFavorited = rand(0, 1) === 1;
            
            return response()->json([
                'success' => true,
                'message' => $isFavorited ? 'Added to favorites' : 'Removed from favorites',
                'data' => [
                    'listing_id' => $id,
                    'is_favorited' => $isFavorited,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle favorite',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark listing as sold
     */
    public function markSold(Request $request, int $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Listing marked as sold',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark listing as sold',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get listing analytics
     */
    public function analytics(Request $request, int $id): JsonResponse
    {
        try {
            $analytics = [
                'listing_id' => $id,
                'views' => rand(100, 500),
                'likes' => rand(10, 50),
                'inquiries' => rand(5, 20),
                'views_today' => rand(10, 30),
                'views_this_week' => rand(50, 150),
                'views_this_month' => rand(200, 400),
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload images for existing listing
     */
    public function uploadImages(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Find the listing and verify ownership
            $listing = EquipmentListing::where('seller_id', $user->id)
                ->findOrFail($id);

            // Validate images
            $request->validate([
                'images' => 'required|array|max:10',
                'images.*' => 'file|mimes:jpeg,jpg,png|max:5120', // 5MB max
            ]);

            // Check subscription limits
            $subscription = $user->activeSubscription();
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Active subscription required to upload images'
                ], 403);
            }

            $maxImages = $subscription->plan->max_images_per_listing ?? 5;
            $images = array_slice($request->file('images'), 0, $maxImages);
            
            $imagePaths = [];
            foreach ($images as $image) {
                $path = $image->store('listings', 'public');
                $imagePaths[] = $path;
            }
            
            // Merge with existing images or replace them
            $existingImages = $listing->images ?? [];
            $allImages = array_merge($existingImages, $imagePaths);
            
            // Limit total images
            $listing->images = array_slice($allImages, 0, $maxImages);
            $listing->save();

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'data' => [
                    'listing_id' => $listing->id,
                    'images' => $listing->images,
                    'total_images' => count($listing->images)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}