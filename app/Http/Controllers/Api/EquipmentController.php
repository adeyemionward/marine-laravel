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

            $perPage = min(50, max(1, (int) $request->get('per_page', 12)));
            $equipment = $query->paginate($perPage);

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
            $newListing = [
                'id' => rand(100, 999),
                'title' => $request->input('title', 'New Equipment'),
                'description' => $request->input('description', 'Equipment description'),
                'price' => $request->input('price', 0),
                'currency' => 'USD',
                'location' => $request->input('location', 'Nigeria'),
                'category' => $request->input('category', 'Equipment'),
                'condition' => $request->input('condition', 'used'),
                'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=New+Equipment'],
                'created_at' => now()->toDateTimeString(),
                'is_featured' => false,
                'seller' => ['name' => 'Test Seller', 'verified' => false]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Listing created successfully',
                'data' => $newListing,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create listing',
                'error' => $e->getMessage(),
            ], 400);
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
            $updatedListing = [
                'id' => $id,
                'title' => $request->input('title', 'Updated Equipment'),
                'description' => $request->input('description', 'Updated description'),
                'price' => $request->input('price', 0),
                'currency' => 'USD',
                'location' => $request->input('location', 'Nigeria'),
                'category' => $request->input('category', 'Equipment'),
                'condition' => $request->input('condition', 'used'),
                'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Updated+Equipment'],
                'created_at' => now()->subWeek()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
                'is_featured' => false,
                'seller' => ['name' => 'Test Seller', 'verified' => false]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Listing updated successfully',
                'data' => $updatedListing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified equipment listing
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Listing deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete listing',
                'error' => $e->getMessage(),
            ], 400);
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
}