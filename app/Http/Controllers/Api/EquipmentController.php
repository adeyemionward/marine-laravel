<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{

    /**
     * Display a paginated listing of equipment
     */
    public function index(Request $request): JsonResponse
    {
        // Sample equipment data for testing
        $sampleEquipment = [
            [
                'id' => 1,
                'title' => 'Yamaha F115 Outboard Engine',
                'description' => 'Reliable 115HP 4-stroke outboard engine in excellent condition',
                'price' => 12500,
                'currency' => 'USD',
                'location' => 'Lagos, Nigeria',
                'category' => 'Engines',
                'condition' => 'used',
                'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Yamaha+Engine'],
                'created_at' => now()->subDays(2)->toDateTimeString(),
                'is_featured' => true,
                'seller' => ['name' => 'Marine Equipment Store', 'verified' => true]
            ],
            [
                'id' => 2,
                'title' => 'Boston Whaler 210 Montauk',
                'description' => 'Classic fishing boat with twin engines, perfect for offshore fishing',
                'price' => 45000,
                'currency' => 'USD',
                'location' => 'Port Harcourt, Nigeria',
                'category' => 'Boats',
                'condition' => 'used',
                'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Boston+Whaler'],
                'created_at' => now()->subDays(5)->toDateTimeString(),
                'is_featured' => false,
                'seller' => ['name' => 'Coastal Marine', 'verified' => true]
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $sampleEquipment,
            'meta' => [
                'current_page' => 1,
                'per_page' => 10,
                'total' => 2,
                'last_page' => 1,
                'has_more' => false,
            ],
            'message' => 'Equipment listings retrieved successfully'
        ]);
    }

    /**
     * Get featured listings
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min(20, max(1, (int) $request->get('limit', 10)));
        
        $featuredEquipment = [
            [
                'id' => 1,
                'title' => 'Yamaha F115 Outboard Engine',
                'description' => 'Reliable 115HP 4-stroke outboard engine in excellent condition',
                'price' => 12500,
                'currency' => 'USD',
                'location' => 'Lagos, Nigeria',
                'category' => 'Engines',
                'condition' => 'used',
                'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Yamaha+Engine'],
                'created_at' => now()->subDays(2)->toDateTimeString(),
                'is_featured' => true,
                'seller' => ['name' => 'Marine Equipment Store', 'verified' => true]
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => array_slice($featuredEquipment, 0, $limit),
            'meta' => [
                'count' => count($featuredEquipment),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Get popular listings
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = min(20, max(1, (int) $request->get('limit', 10)));
        
        $popularEquipment = [
            [
                'id' => 2,
                'title' => 'Boston Whaler 210 Montauk',
                'description' => 'Classic fishing boat with twin engines, perfect for offshore fishing',
                'price' => 45000,
                'currency' => 'USD',
                'location' => 'Port Harcourt, Nigeria',
                'category' => 'Boats',
                'condition' => 'used',
                'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Boston+Whaler'],
                'created_at' => now()->subDays(5)->toDateTimeString(),
                'is_featured' => false,
                'seller' => ['name' => 'Coastal Marine', 'verified' => true]
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => array_slice($popularEquipment, 0, $limit),
            'meta' => [
                'count' => count($popularEquipment),
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Search equipment listings
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
        ]);

        $query = $request->get('q');
        
        $searchResults = [
            [
                'id' => 1,
                'title' => 'Yamaha F115 Outboard Engine',
                'description' => 'Reliable 115HP 4-stroke outboard engine in excellent condition',
                'price' => 12500,
                'currency' => 'USD',
                'location' => 'Lagos, Nigeria',
                'category' => 'Engines',
                'condition' => 'used',
                'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Yamaha+Engine'],
                'created_at' => now()->subDays(2)->toDateTimeString(),
                'is_featured' => true,
                'seller' => ['name' => 'Marine Equipment Store', 'verified' => true]
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $searchResults,
            'meta' => [
                'query' => $query,
                'current_page' => 1,
                'per_page' => 10,
                'total' => count($searchResults),
                'last_page' => 1,
                'has_more' => false,
            ],
        ]);
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
            $sampleListings = [
                1 => [
                    'id' => 1,
                    'title' => 'Yamaha F115 Outboard Engine',
                    'description' => 'Reliable 115HP 4-stroke outboard engine in excellent condition',
                    'price' => 12500,
                    'currency' => 'USD',
                    'location' => 'Lagos, Nigeria',
                    'category' => 'Engines',
                    'condition' => 'used',
                    'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Yamaha+Engine'],
                    'created_at' => now()->subDays(2)->toDateTimeString(),
                    'is_featured' => true,
                    'seller' => ['name' => 'Marine Equipment Store', 'verified' => true]
                ],
                2 => [
                    'id' => 2,
                    'title' => 'Boston Whaler 210 Montauk',
                    'description' => 'Classic fishing boat with twin engines, perfect for offshore fishing',
                    'price' => 45000,
                    'currency' => 'USD',
                    'location' => 'Port Harcourt, Nigeria',
                    'category' => 'Boats',
                    'condition' => 'used',
                    'images' => ['https://via.placeholder.com/400x300/0066cc/ffffff?text=Boston+Whaler'],
                    'created_at' => now()->subDays(5)->toDateTimeString(),
                    'is_featured' => false,
                    'seller' => ['name' => 'Coastal Marine', 'verified' => true]
                ],
            ];

            $listing = $sampleListings[$id] ?? null;

            if (!$listing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $listing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve listing',
                'error' => $e->getMessage(),
            ], 500);
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