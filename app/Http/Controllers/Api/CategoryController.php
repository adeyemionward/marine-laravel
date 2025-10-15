<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EquipmentCategory;
use App\Models\EquipmentListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // For admin, return all categories with all necessary fields
            // For public, only return active categories
            $categories = EquipmentCategory::query()
                ->withCount(['listings', 'children'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'slug',
                    'description',
                    'icon_name',
                    'sort_order',
                    'parent_id',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]);

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $category = EquipmentCategory::active()
                ->with(['listings' => function($query) {
                    $query->active()->with(['seller', 'category'])->take(10);
                }])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon_name' => $category->icon_name,
                    'listings_count' => $category->listings()->active()->count(),
                    'recent_listings' => $category->listings,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get category statistics for admin dashboard
     */
    public function getStats(): JsonResponse
    {
        try {
            // Count categories with parent/child relationships
            $mainCategories = EquipmentCategory::whereNull('parent_id')->count();
            $subcategories = EquipmentCategory::whereNotNull('parent_id')->count();

            $stats = [
                'totalCategories' => EquipmentCategory::count(),
                'activeCategories' => EquipmentCategory::where('is_active', true)->count(),
                'inactiveCategories' => EquipmentCategory::where('is_active', false)->count(),
                'mainCategories' => $mainCategories,
                'subcategories' => $subcategories,
                'categoriesWithListings' => EquipmentCategory::whereHas('listings')->count(),
                'emptyCategories' => EquipmentCategory::doesntHave('listings')->count(),
                'totalListings' => EquipmentListing::count(),
                'avgListingsPerCategory' => round(EquipmentListing::count() / max(EquipmentCategory::count(), 1), 2),
                'mostPopularCategory' => EquipmentCategory::withCount('listings')
                    ->orderBy('listings_count', 'desc')
                    ->first(['name', 'listings_count']),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Category stats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'debug_info' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Store a new category
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:equipment_categories,name',
                'description' => 'nullable|string|max:500',
                'parent_id' => 'nullable|exists:equipment_categories,id',
                'icon_name' => 'nullable|string|max:100',
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'nullable|in:active,inactive'
            ]);

            $validated['slug'] = Str::slug($validated['name']);
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['sort_order'] = $validated['sort_order'] ?? 0;

            $category = EquipmentCategory::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update an existing category
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $category = EquipmentCategory::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:equipment_categories,name,' . $id,
                'description' => 'nullable|string|max:500',
                'parent_id' => 'nullable|exists:equipment_categories,id',
                'icon_name' => 'nullable|string|max:100',
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'nullable|in:active,inactive'
            ]);

            $validated['slug'] = Str::slug($validated['name']);

            $category->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a category
     */
    public function destroy($id): JsonResponse
    {
        try {
            $category = EquipmentCategory::findOrFail($id);

            // Check if category has listings
            if ($category->listings()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with existing listings'
                ], 400);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
