<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentListing;
use App\Models\User;
use App\Models\UserFavorite;
use App\Models\SellerReview;
use App\Http\Resources\EquipmentListingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
                // ->published()
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
            Log::info('EquipmentController::store START', [
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
            Log::info('Validation data', [
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
                'listing_type' => 'nullable|in:sale,lease,rent', // Add listing_type validation
                'condition' => 'required|in:new,new_like,like_new,excellent,good,fair,poor', // Updated conditions
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

            // Additional validation: Price is required for 'sale' listings if not POA
            // For 'lease' and 'rent' listings, price is optional (can be negotiated)
            $listingType = $request->get('listing_type', 'sale');
            $isPriceRequired = $listingType === 'sale' && !$request->boolean('is_poa');

            if ($isPriceRequired && (!$request->has('price') || $request->price === null || $request->price === '')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => [
                        'price' => ['The price field is required for sale listings when not using Price on Application.']
                    ]
                ], 422);
            }

            $user = $request->user();

            // Get user profile ID (required for seller_id foreign key)
            $userProfile = $user->profile;
            if (!$userProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'User profile not found. Please complete your profile first.',
                ], 400);
            }

            Log::info('User subscription check', [
                'user_id' => $user->id,
                'profile_id' => $userProfile->id,
                'has_active_subscription' => method_exists($user, 'activeSubscription')
            ]);

            // Check if user has an active subscription and listing limits
            // Temporarily allow listing creation without subscription for testing
            $subscription = method_exists($user, 'activeSubscription') ? $user->activeSubscription() : null;

            if (!$subscription) {
                Log::warning('No active subscription found, allowing listing creation anyway');
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

                Log::info('Processing images', [
                    'images_count' => count($images),
                    'first_image_type' => gettype($images[0] ?? null)
                ]);

                foreach ($images as $image) {
                    // Check if it's a pre-uploaded image (object/array with url)
                    if (is_array($image) && isset($image['url'])) {
                        // Save the complete image object with all metadata
                        $imagePaths[] = [
                            'url' => $image['url'],
                            'thumbnail_url' => $image['thumbnail_url'] ?? $image['url'],
                            'medium_url' => $image['medium_url'] ?? $image['url'],
                            'large_url' => $image['large_url'] ?? $image['url'],
                            'public_id' => $image['public_id'] ?? null,
                            'width' => $image['width'] ?? null,
                            'height' => $image['height'] ?? null,
                            'size' => $image['size'] ?? null,
                            'is_primary' => $image['is_primary'] ?? false,
                        ];
                    }
                    // Check if it's a file upload
                    elseif ($image instanceof \Illuminate\Http\UploadedFile) {
                        $path = $image->store('listings', 'public');
                        $url = Storage::url($path);
                        $imagePaths[] = [
                            'url' => $url,
                            'thumbnail_url' => $url,
                            'medium_url' => $url,
                            'large_url' => $url,
                            'public_id' => $path,
                            'width' => null,
                            'height' => null,
                            'size' => $image->getSize(),
                            'is_primary' => false,
                        ];
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
                'status' => 'pending', // Changed from 'active' - requires admin approval
                'published_at' => null, // Will be set when admin approves
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
            // ->published()
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
            Log::info('Equipment update request received', [
                'id' => $id,
                'user_id' => $request->user()?->id,
                'data' => $request->except(['images'])
            ]);

            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:2000',
                'price' => 'nullable|numeric|min:0',
                'category_id' => 'sometimes|required|exists:equipment_categories,id',
                'listing_type' => 'nullable|in:sale,lease,rent', // Add listing_type validation
                'condition' => 'sometimes|required|in:new,new_like,like_new,excellent,good,fair,poor', // Updated conditions
                'brand' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
                'location_state' => 'sometimes|required|string|max:100',
                'location_city' => 'nullable|string|max:100',
                'location_address' => 'nullable|string',
                'images' => 'nullable|array|max:10',
                // Images can be either file uploads or already-uploaded image data
                // We'll handle validation separately below
                'specifications' => 'nullable|array',
                'features' => 'nullable|array',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email',
                'contact_whatsapp' => 'nullable|string|max:20',
                'contact_methods' => 'nullable|array',
                'availability_hours' => 'nullable|array',
                'is_price_negotiable' => 'nullable|boolean',
                'is_poa' => 'nullable|boolean',
                'delivery_available' => 'nullable|boolean',
                'delivery_radius' => 'nullable|integer',
                'delivery_fee' => 'nullable|numeric',
                'allows_inspection' => 'nullable|boolean',
                'hide_address' => 'nullable|boolean',
            ]);

            $user = $request->user();

            // Super admin can update any listing, others can only update their own
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                $listing = EquipmentListing::findOrFail($id);
            } else {
                $listing = EquipmentListing::where('seller_id', $user->id)
                    ->findOrFail($id);
            }

            // Prepare update data
            $updateData = $request->only([
                'title', 'description', 'price', 'category_id', 'condition',
                'brand', 'model', 'year', 'location_state', 'location_city', 'location_address',
                'specifications', 'features', 'contact_phone', 'contact_email', 'contact_whatsapp',
                'contact_methods', 'availability_hours', 'is_price_negotiable', 'is_poa',
                'delivery_available', 'delivery_radius', 'delivery_fee', 'allows_inspection',
                'hide_address', 'power_source', 'min_price', 'payment_methods', 'pricing_notes',
                'currency', 'priority_tier'
            ]);

            // Handle images - can be array of image data (already uploaded) or file uploads
            if ($request->has('images')) {
                $imagesData = $request->input('images');

                // Check if this is image data (not file uploads)
                if (is_array($imagesData) && !empty($imagesData)) {
                    $firstImage = $imagesData[0];

                    // If it's already uploaded image data (has 'url' field), use it directly
                    if (is_array($firstImage) && isset($firstImage['url'])) {
                        $updateData['images'] = $imagesData;
                    }
                }
            }

            // Handle new file uploads if provided
            if ($request->hasFile('images')) {
                $subscription = $user->activeSubscription();
                $maxImages = $subscription->plan->max_images_per_listing ?? 5;
                $images = array_slice($request->file('images'), 0, $maxImages);

                $imagePaths = [];
                foreach ($images as $image) {
                    $path = $image->store('listings', 'public');
                    $imagePaths[] = $path;
                }

                $updateData['images'] = $imagePaths;
            }

            $listing->update($updateData);

            Log::info('Equipment update successful', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Listing updated successfully',
                'data' => $listing->load(['category', 'seller']),
            ]);
        } catch (\Exception $e) {
            Log::error('Equipment update failed', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

            // Super admin can delete any listing, others can only delete their own
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                $listing = EquipmentListing::findOrFail($id);
            } else {
                $listing = EquipmentListing::where('seller_id', $user->id)
                    ->findOrFail($id);
            }

            // Soft delete the listing instead of hard delete
            $listing->update([
                'status' => 'archived',
                'deleted_at' => now(),
            ]);

            // Update seller profile listing count
            $seller = User::find($listing->seller_id);
            if ($seller && $seller->sellerProfile) {
                $seller->sellerProfile->updateListingCount();
            }

            return response()->json([
                'success' => true,
                'message' => 'Listing deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found or you do not have permission to delete it',
            ], 404);
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
            // Super admin can update any listing, others can only update their own
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                $listing = EquipmentListing::findOrFail($id);
            } else {
                $listing = EquipmentListing::where('seller_id', $user->id)
                    ->findOrFail($id);
            }

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
    public function fetchReview(Request $request)
    {
        try {
            $listingId = $request->query('listing_id');

            if (!$listingId) {
                // If no listing_id, return current user's reviews
                $reviewer_id = Auth::id();
                $reviews = SellerReview::with(['reviewer', 'listing'])
                    ->where('reviewer_id', $reviewer_id)
                    ->latest()
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $reviews
                ]);
            }

            // Get all reviews for the listing
            $query = SellerReview::with(['reviewer', 'listing'])
                ->where('listing_id', $listingId);

            // Apply sorting
            $sortBy = $request->query('sort_by', 'recent');
            switch ($sortBy) {
                case 'helpful':
                    $query->orderByDesc('helpful_count');
                    break;
                case 'rating_high':
                    $query->orderByDesc('rating');
                    break;
                case 'rating_low':
                    $query->orderBy('rating');
                    break;
                case 'recent':
                default:
                    $query->latest();
                    break;
            }

            // Pagination
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $reviews = $query->paginate($perPage);

            // Calculate stats
            $allReviews = SellerReview::where('listing_id', $listingId)->get();
            $stats = [
                'total_reviews' => $allReviews->count(),
                'average_rating' => $allReviews->avg('rating') ?? 0,
                'rating_distribution' => [
                    '5' => $allReviews->where('rating', 5)->count(),
                    '4' => $allReviews->where('rating', 4)->count(),
                    '3' => $allReviews->where('rating', 3)->count(),
                    '2' => $allReviews->where('rating', 2)->count(),
                    '1' => $allReviews->where('rating', 1)->count(),
                ]
            ];

            // Transform reviews to include user data properly
            $transformedReviews = $reviews->getCollection()->map(function ($review) {
                return [
                    'id' => $review->id,
                    'listing_id' => $review->listing_id,
                    'rating' => $review->rating,
                    'comment' => $review->review,
                    'review' => $review->review,
                    'user_id' => $review->reviewer_id,
                    'user' => [
                        'id' => $review->reviewer_id,
                        'name' => $review->reviewer->name ?? 'Anonymous',
                        'email' => $review->reviewer->email ?? null,
                    ],
                    'helpful_count' => $review->helpful_count ?? 0,
                    'not_helpful_count' => $review->not_helpful_count ?? 0,
                    'seller_reply' => $review->seller_reply ?? null,
                    'seller_replied_at' => $review->seller_replied_at ?? null,
                    'is_verified_purchase' => $review->is_verified_purchase ?? false,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                ];
            });

            $reviews->setCollection($transformedReviews);

            return response()->json([
                'success' => true,
                'data' => $reviews->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch reviews: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reviews',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Store a review
    public function addReview(Request $request)
    {
        try {
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
                        'status'=>false,

                    ], 404);
             }
             $reviewer_id =  Auth::id();

            // Check if user has already reviewed this listing
            $existingReview = SellerReview::where('seller_id', $productDetails->seller_id)
                ->where('reviewer_id', $reviewer_id)
                ->where('listing_id', $listingID)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'message' => 'You have already reviewed this listing',
                    'status' => false,
                ], 422);
            }

            // Prevent seller from reviewing their own listing
            if ($productDetails->seller_id == $reviewer_id) {
                return response()->json([
                    'message' => 'You cannot review your own listing',
                    'status' => false,
                ], 422);
            }

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

            return response()->json([
                'message' => 'Review submitted successfully',
                'status' => true,
                'data' => $review
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to add review: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to submit review. Please try again.',
                'status' => false,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
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

    // Mark review as helpful
    public function markHelpful($id)
    {
        try {
            $review = SellerReview::findOrFail($id);

            // Increment helpful count
            $review->increment('helpful_count');

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback',
                'data' => [
                    'helpful_count' => $review->helpful_count,
                    'not_helpful_count' => $review->not_helpful_count,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark review as helpful: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark review as helpful',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Mark review as not helpful
    public function markNotHelpful($id)
    {
        try {
            $review = SellerReview::findOrFail($id);

            // Increment not helpful count
            $review->increment('not_helpful_count');

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback',
                'data' => [
                    'helpful_count' => $review->helpful_count,
                    'not_helpful_count' => $review->not_helpful_count,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark review as not helpful: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark review as not helpful',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
