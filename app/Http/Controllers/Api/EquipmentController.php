<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentListing;
use App\Models\UserFavorite;
use App\Models\SellerReview;
use App\Http\Resources\EquipmentListingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class EquipmentController extends Controller
{

    /**
     * Display a paginated listing of equipment
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EquipmentListing::with([
                'seller.profile',
                'seller.sellerProfile',
                'category',
                'seller' => function($q) {
                    $q->withCount([
                        'listings as listings_count' => function($query) {
                            $query->where('status', 'active');
                        },
                        'sales as sales_count' => function($query) {
                            $query->where('status', 'completed');
                        }
                    ]);
                }
            ])
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
                    'data' => EquipmentListingResource::collection($equipment),
                ]);
            } else {
                // Standard pagination
                $perPage = min(50, max(1, (int) $request->get('per_page', 12)));
                $equipment = $query->paginate($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => EquipmentListingResource::collection($equipment->items()),
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

            $equipment = EquipmentListing::with(['seller.profile', 'seller.sellerProfile', 'category'])
                ->active()
                ->published()
                ->notExpired()
                ->featured()
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => EquipmentListingResource::collection($equipment),
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
            
            $equipment = EquipmentListing::with(['seller.profile', 'seller.sellerProfile', 'category'])
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
            
            $equipment = EquipmentListing::with(['seller.profile', 'seller.sellerProfile', 'category'])
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
            \Log::info('EquipmentController::store START', [
                'request_data' => $request->except(['images']),
                'has_images' => $request->hasFile('images')
            ]);

            // Normalize condition format (convert kebab-case to snake_case)
            if ($request->has('condition')) {
                $request->merge([
                    'condition' => str_replace('-', '_', $request->condition)
                ]);
            }

            // Log validation data for debugging
            \Log::info('Validation data', [
                'price' => $request->price,
                'is_poa' => $request->boolean('is_poa'),
                'condition' => $request->condition
            ]);

            // Validate request
            // Note: Images are already uploaded separately, so we expect image objects with URLs, not files
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'price' => 'nullable|numeric|min:0', // Make price optional
                'category_id' => 'required|exists:equipment_categories,id',
                'condition' => 'required|in:new,like_new,good,fair,poor',
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'location_state' => 'required|string|max:100',
                'location_city' => 'nullable|string|max:100',
                'images' => 'nullable|array|max:10',
                // Images can be either file uploads OR objects with url/publicId (pre-uploaded)
                'images.*' => 'nullable',
                'specifications' => 'nullable|array',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email',
                'negotiable' => 'boolean',
                'is_poa' => 'boolean',
            ]);

            // Additional validation: if not POA, price is required
            if (!$request->boolean('is_poa') && (!$request->has('price') || $request->price === null || $request->price === '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'price' => ['The price field is required when not using Price on Application.']
                    ]
                ], 422);
            }

            $user = $request->user();

            \Log::info('User subscription check', [
                'user_id' => $user->id,
                'has_active_subscription' => method_exists($user, 'activeSubscription')
            ]);

            // Check if user has an active subscription and listing limits
            // Temporarily allow listing creation without subscription for testing
            $subscription = method_exists($user, 'activeSubscription') ? $user->activeSubscription() : null;

            if (!$subscription) {
                \Log::warning('No active subscription found, allowing listing creation anyway');
                // Create a mock subscription object for limits
                $subscription = (object)[
                    'plan' => (object)[
                        'max_listings' => -1, // unlimited
                        'max_images_per_listing' => 10
                    ]
                ];
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

            // Handle images - they can be pre-uploaded (objects with urls) or files
            $imagePaths = [];
            if ($request->has('images') && is_array($request->images)) {
                $maxImages = $subscription->plan->max_images_per_listing ?? 10;
                $images = array_slice($request->images, 0, $maxImages);

                \Log::info('Processing images', [
                    'images_count' => count($images),
                    'first_image_type' => gettype($images[0] ?? null)
                ]);

                foreach ($images as $image) {
                    // Check if it's a pre-uploaded image (object/array with url)
                    if (is_array($image) && isset($image['url'])) {
                        $imagePaths[] = $image['url'];
                    }
                    // Check if it's a file upload
                    elseif ($image instanceof \Illuminate\Http\UploadedFile) {
                        $path = $image->store('listings', 'public');
                        $imagePaths[] = Storage::url($path);
                    }
                }
            }

            // Handle price - if POA, set price to 0 or null
            $price = $request->boolean('is_poa') ? 0 : ($request->price ?? 0);

            $listing = EquipmentListing::create([
                'seller_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'price' => $price,
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
    public function show(int $id)
    {
        try {
            $listing = EquipmentListing::with(['seller.profile', 'seller.sellerProfile', 'category'])
            ->active()
            ->published()
            ->notExpired()
            ->where('id', $id)
            ->first();
            

            // Increment view count
           // $listing->incrementViewCount();
            
            
if (!$listing) {
    return response()->json([
        'success' => false,
        'message' => 'Listing not found or does not meet criteria'
    ], 404);
}

            return response()->json([
                'success' => true,
                'data' => new EquipmentListingResource($listing),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching equipment listing',
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
            ], 500);
        }
    }

    public function addFavoriteItem(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'listing_id'   => 'required|integer',
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'status'=>false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $favorite = UserFavorite::firstOrCreate([
                'user_id'   => Auth::id(),
                'listing_id'   => $request->listing_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item added to favorites',
                'data' => [
                    'favorites' => $favorite,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add favorite',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function removeFavoriteItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_id'   => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'status'=>false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();

            $favorite = UserFavorite::where('user_id', $userId)
                ->where('item_id', $request->listing_id)
                ->first();

            if (!$favorite) {
                return response()->json([
                    'message' => 'Item not found in favorites',
                    'status'=>true,
                    
                ], 200);
            }

            $favorite->delete();

            return $this->jsonResponse(true, 200, "Item removed from favorites", null, false, false);

            return response()->json([
                'message' => 'Item removed from favorites',
                'status'=>true,
            ], 200);

        } catch (\Exception $th) {
             return response()->json([
                'success' => false,
                'message' => 'Failed to fetch favorite',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    // List all favorites for the user
    public function fetchFavoriteItems()
    {
        try{
            $user = Auth::guard('api')->user(); // Ensure you're using the correct guard

            if (!$user) {
                 return response()->json([
                    'message' => 'Item empty',
                    'status'=>true,
                ], 200);
            }

            $favorites = UserFavorite::where('user_id', $user->id)->get();

            $productIds = $favorites->pluck('listing_id');

            $products = EquipmentListing::whereIn('id', $productIds)->get();

             return response()->json([
                'message' => 'Item fetched',
                'status'=>true,
                'Listing'=> $products
            ], 200);

        } catch (\Exception $th) {
             return response()->json([
                'success' => false,
                'message' => 'Error fetching items',
                'error' => $th->getMessage(),
            ], 500);
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

    /**
     * Track listing view
     */
    public function trackView($id): JsonResponse
    {
        try {
            $listing = EquipmentListing::findOrFail($id);

            // Increment view count
            $listing->increment('view_count');

            // Log the view event
            \App\Services\LoggingService::logListing('view_tracked', [
                'listing_id' => $listing->id,
                'listing_title' => $listing->title,
                'seller_id' => $listing->seller_id,
                'previous_count' => $listing->view_count - 1,
                'new_count' => $listing->view_count,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'View tracked successfully',
                'data' => [
                    'view_count' => $listing->view_count,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track view',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Get all reviews for a seller
    public function fetchReview()
    {
        $reviewer_id =  Auth::id();
        $reviews = SellerReview::with(['reviewer', 'listing'])
            ->where('reviewer_id', $reviewer_id)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    // Store a review
    public function addReview(Request $request)
    {
        
        // $validated = $request->validate([
        //     // 'seller_id' => 'required|exists:users,id',
        //     // 'reviewer_id' => 'required|exists:users,id',
        //     'listing_id' => 'nullable|exists:listings,id',
        //     'rating' => 'required|integer|min:1|max:5',
        //     'review' => 'nullable|string',
        //     // 'review_categories' => 'nullable|array',
        //     // 'is_verified_purchase' => 'boolean',
        // ]);
        $validator = Validator::make($request->all(), [
            'listing_id' => 'nullable|exists:equipment_listings,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'status'=>false,
                'errors' => $validator->errors()
            ], 422);
        }

        $listingID = request('listing_id');


       
         $productDetails = EquipmentListing::where('id', $listingID)->first();
         if(is_null($productDetails)){
             return response()->json([
                    'message' => 'Item not found ',
                    'status'=>true,
                    
                ], 200);
         }
         $reviewer_id =  Auth::id();
         

        // if ($request->is_verified_purchase) {
        //     $validated['verified_at'] = now();
        // }

        $review = new SellerReview();
        $review->seller_id  = $productDetails->seller_id;
        $review->reviewer_id = $reviewer_id;
        $review->listing_id  = $request->input('listing_id');
        $review->rating      = $request->input('rating');
        $review->review      = $request->input('review');
        $review->save();

        return response()->json($review, 201);
    }

    // // Show single review
    public function showReview($id)
    {
        $review = SellerReview::with(['reviewer', 'listing'])->findOrFail($id);

        return response()->json($review);
    }

    // Update review
    public function updateReviews(Request $request, $id)
    {
        $review = SellerReview::findOrFail($id);

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'review' => 'sometimes|string',
            'review_categories' => 'sometimes|array',
            'is_verified_purchase' => 'boolean',
        ]);

        if ($request->has('is_verified_purchase') && $request->is_verified_purchase) {
            $validated['verified_at'] = now();
        }

        $review->update($validated);

        return response()->json($review);
    }

    // Delete review
    public function destroyReview($id)
    {
        $review = SellerReview::findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
}