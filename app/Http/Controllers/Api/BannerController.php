<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $banners = Banner::active()
                ->orderBy('priority')
                ->orderBy('created_at', 'desc')
                ->get(['id', 'title', 'description', 'media_url', 'link_url', 'position', 'priority']);

            return response()->json([
                'success' => true,
                'data' => $banners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banners',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'image_url' => 'required|url',
                'link_url' => 'nullable|url',
                'link_text' => 'nullable|string|max:100',
                'position' => 'required|in:hero,sidebar,footer,popup',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ]);

            $validated['created_by'] = Auth::user()->profile->id;
            $validated['sort_order'] = $validated['sort_order'] ?? 0;

            $banner = Banner::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => $banner,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create banner',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $banner = Banner::with('creator')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'description' => $banner->description,
                    'image_url' => $banner->image_url,
                    'link_url' => $banner->link_url,
                    'link_text' => $banner->link_text,
                    'position' => $banner->position,
                    'sort_order' => $banner->sort_order,
                    'is_active' => $banner->is_active,
                    'clicks' => $banner->clicks,
                    'impressions' => $banner->impressions,
                    'created_by' => $banner->creator ? [
                        'id' => $banner->creator->id,
                        'name' => $banner->creator->full_name,
                    ] : null,
                    'created_at' => $banner->created_at,
                    'updated_at' => $banner->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $banner = Banner::findOrFail($id);

            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:500',
                'image_url' => 'sometimes|url',
                'link_url' => 'nullable|url',
                'link_text' => 'nullable|string|max:100',
                'position' => 'sometimes|in:hero,sidebar,footer,popup',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ]);

            $banner->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => $banner->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Banner deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete banner',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}