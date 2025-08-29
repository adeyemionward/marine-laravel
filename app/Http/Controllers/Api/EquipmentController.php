<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Http\Requests\ListingFilterRequest;
use App\Http\Resources\EquipmentListingResource;
use App\Http\Resources\EquipmentListingCollection;
use App\Services\EquipmentService;
use App\DTOs\ListingFilterDTO;
use App\DTOs\CreateListingDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function __construct(
        private EquipmentService $equipmentService
    ) {}

    /**
     * Display a paginated listing of equipment
     */
    public function index(ListingFilterRequest $request): JsonResponse
    {
        $filters = ListingFilterDTO::fromRequest($request->validated());
        $listings = $this->equipmentService->getListings($filters);

        return response()->json([
            'success' => true,
            'data' => new EquipmentListingCollection($listings),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'last_page' => $listings->lastPage(),
                'has_more' => $listings->hasMorePages(),
            ],
            'filters_applied' => $filters->hasFilters(),
        ]);
    }

    /**
     * Get featured listings
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min(20, max(1, (int) $request->get('limit', 10)));
        $listings = $this->equipmentService->getFeaturedListings($limit);

        return response()->json([
            'success' => true,
            'data' => EquipmentListingResource::collection($listings),
            'meta' => [
                'count' => $listings->count(),
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
        $listings = $this->equipmentService->getPopularListings($limit);

        return response()->json([
            'success' => true,
            'data' => EquipmentListingResource::collection($listings),
            'meta' => [
                'count' => $listings->count(),
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
        $filters = ListingFilterDTO::fromRequest($request->all());
        $listings = $this->equipmentService->searchListings($query, $filters);

        return response()->json([
            'success' => true,
            'data' => new EquipmentListingCollection($listings),
            'meta' => [
                'query' => $query,
                'current_page' => $listings->currentPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'last_page' => $listings->lastPage(),
                'has_more' => $listings->hasMorePages(),
            ],
        ]);
    }

    /**
     * Store a newly created equipment listing
     */
    public function store(CreateListingRequest $request): JsonResponse
    {
        try {
            $dto = CreateListingDTO::fromRequest($request->validated());
            $listing = $this->equipmentService->createListing($dto, $request->user()->profile);

            return response()->json([
                'success' => true,
                'message' => 'Listing created successfully',
                'data' => new EquipmentListingResource($listing),
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
            $listing = $this->equipmentService->getListingDetail($id);

            if (!$listing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new EquipmentListingResource($listing),
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
    public function update(UpdateListingRequest $request, int $id): JsonResponse
    {
        try {
            $updated = $this->equipmentService->updateListing(
                $id,
                $request->validated(),
                $request->user()->profile
            );

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found',
                ], 404);
            }

            $listing = $this->equipmentService->getListingDetail($id, false);

            return response()->json([
                'success' => true,
                'message' => 'Listing updated successfully',
                'data' => new EquipmentListingResource($listing),
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
            $deleted = $this->equipmentService->deleteListing($id, $request->user()->profile);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found',
                ], 404);
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
            ], 400);
        }
    }

    /**
     * Toggle favorite status for a listing
     */
    public function toggleFavorite(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->equipmentService->toggleFavorite($id, $request->user()->profile);

            return response()->json([
                'success' => true,
                'message' => $result['is_favorited'] ? 'Added to favorites' : 'Removed from favorites',
                'data' => $result,
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
            $updated = $this->equipmentService->markAsSold($id, $request->user()->profile);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found',
                ], 404);
            }

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
            $analytics = $this->equipmentService->getListingAnalytics($id, $request->user()->profile);

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