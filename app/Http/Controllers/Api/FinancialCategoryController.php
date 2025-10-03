<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FinancialCategoryController extends Controller
{
    /**
     * Get all financial categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = FinancialCategory::query();

            // Filter by type
            if ($request->filled('type')) {
                $query->ofType($request->type);
            }

            // Filter by active status
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by system/custom
            if ($request->filled('is_system')) {
                $query->where('is_system', $request->boolean('is_system'));
            }

            $categories = $query->orderBy('type')->orderBy('name')->get();

            // Add transaction stats to each category
            $categories->transform(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'description' => $category->description,
                    'color' => $category->color,
                    'is_system' => $category->is_system,
                    'is_active' => $category->is_active,
                    'transaction_count' => $category->transactions()->count(),
                    'total_amount' => $category->transactions()->sum('amount'),
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            \Log::error('Financial Category Fetch Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new category
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:financial_categories,name',
                'type' => 'required|in:income,expense',
                'description' => 'nullable|string|max:1000',
                'color' => 'nullable|string|max:20',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category = FinancialCategory::create([
                'name' => $request->name,
                'type' => $request->type,
                'description' => $request->description,
                'color' => $request->color,
                'is_system' => false, // Custom categories are never system categories
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Financial Category Creation Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a category
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $category = FinancialCategory::findOrFail($id);

            // Prevent editing system categories' critical fields
            if ($category->is_system) {
                $validator = Validator::make($request->all(), [
                    'description' => 'nullable|string|max:1000',
                    'color' => 'nullable|string|max:20',
                    'is_active' => 'boolean'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Only allow updating description, color, and is_active for system categories
                $category->update($request->only(['description', 'color', 'is_active']));
            } else {
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string|max:255|unique:financial_categories,name,' . $id,
                    'type' => 'required|in:income,expense',
                    'description' => 'nullable|string|max:1000',
                    'color' => 'nullable|string|max:20',
                    'is_active' => 'boolean'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $category->update($request->only(['name', 'type', 'description', 'color', 'is_active']));
            }

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Financial Category Update Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a category
     */
    public function destroy($id): JsonResponse
    {
        try {
            $category = FinancialCategory::findOrFail($id);

            // Prevent deleting system categories
            if ($category->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System categories cannot be deleted'
                ], 403);
            }

            // Check if category has transactions
            $transactionCount = $category->transactions()->count();
            if ($transactionCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete category with {$transactionCount} existing transactions. Please reassign or delete the transactions first."
                ], 409);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Financial Category Delete Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $category = FinancialCategory::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type,
                    'description' => $category->description,
                    'color' => $category->color,
                    'is_system' => $category->is_system,
                    'is_active' => $category->is_active,
                    'transaction_count' => $category->transactions()->count(),
                    'total_amount' => $category->transactions()->sum('amount'),
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
    }
}
