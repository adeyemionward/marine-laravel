<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Banner::active()->paid();

            // Filter by display context (homepage, category, etc.)
            if ($request->has('context')) {
                $query->forContext($request->input('context'));
            }

            // Filter by position (hero, category_row, etc.)
            if ($request->has('position')) {
                $query->forPosition($request->input('position'));
            }

            // Filter by banner type
            if ($request->has('type')) {
                $query->forBannerType($request->input('type'));
            }

            // Filter by device type
            if ($request->has('device')) {
                $query->forDevice($request->input('device'));
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->forCategory($request->input('category_id'));
            }

            // Exclude banners that have reached their limits
            $query->where(function($q) {
                $q->whereNull('max_impressions')
                  ->orWhereRaw('impression_count < max_impressions');
            });

            $query->where(function($q) {
                $q->whereNull('max_clicks')
                  ->orWhereRaw('click_count < max_clicks');
            });

            $banners = $query
                ->byPriority()
                ->with('targetCategory')
                ->get([
                    'id',
                    'title',
                    'description',
                    'media_type',
                    'media_url',
                    'link_url',
                    'banner_type',
                    'position',
                    'priority',
                    'banner_size',
                    'dimensions',
                    'mobile_dimensions',
                    'display_context',
                    'background_color',
                    'text_color',
                    'button_text',
                    'button_color',
                    'overlay_settings',
                    'target_category_id',
                    'start_date',
                    'end_date'
                ]);

            // Group banners by position for easier frontend consumption
            $groupedBanners = $banners->groupBy('position');

            return response()->json([
                'success' => true,
                'data' => $groupedBanners,
                'total' => $banners->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banners',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function trackClick(Request $request, $id): JsonResponse
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->increment('click_count');

            return response()->json([
                'success' => true,
                'message' => 'Click tracked successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track click',
            ], 500);
        }
    }

    public function trackImpression(Request $request, $id): JsonResponse
    {
        try {
            $banner = Banner::findOrFail($id);

            // Check if banner has reached max impressions
            if ($banner->hasReachedMaxImpressions()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Banner has reached maximum impressions',
                ], 400);
            }

            $banner->increment('impression_count');
            $banner->updateConversionRate();

            return response()->json([
                'success' => true,
                'message' => 'Impression tracked successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track impression',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
                'media_url' => 'required|string',
                'link_url' => 'nullable|url',
                'banner_position' => 'required|string',
                'priority' => 'nullable|integer|min:0',
                'status' => 'nullable|in:active,inactive,expired',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'media_type' => 'nullable|in:image,video',
                'customer_id' => 'nullable|integer',
                'banner_charge' => 'nullable|numeric|min:0',
                'purchaser_id' => 'nullable|integer',
                'purchase_price' => 'nullable|numeric|min:0',
            ]);

            // Map banner_position to position field
            if (isset($validated['banner_position'])) {
                $validated['position'] = $validated['banner_position'];
                unset($validated['banner_position']);
            }

            // Map banner_charge to purchase_price field
            if (isset($validated['banner_charge'])) {
                $validated['purchase_price'] = $validated['banner_charge'];
                unset($validated['banner_charge']);
            }

            // Map customer_id to purchaser_id (if customer_id is sent, use it as purchaser_id)
            if (isset($validated['customer_id'])) {
                $validated['purchaser_id'] = $validated['customer_id'];
                unset($validated['customer_id']);
            }

            $validated['created_by'] = Auth::user()->profile->id;
            $validated['priority'] = $validated['priority'] ?? 1;
            $validated['media_type'] = $validated['media_type'] ?? 'image';
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['banner_size'] = Banner::SIZE_LARGE;
            $validated['display_context'] = Banner::CONTEXT_HOMEPAGE;
            $validated['show_on_desktop'] = true;
            $validated['show_on_mobile'] = true;
            $validated['sort_order'] = 0;
            $validated['purchase_status'] = 'paid';
            $validated['user_target'] = 'all';
            $validated['banner_type'] = Banner::TYPE_PROMOTIONAL;
            $validated['start_date'] = $validated['start_date'] ?? now();

            $banner = Banner::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => $banner,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Banner creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
                'media_url' => 'sometimes|string',
                'link_url' => 'nullable|url',
                'banner_position' => 'sometimes|string',
                'priority' => 'sometimes|integer|min:0',
                'status' => 'sometimes|in:active,inactive,expired',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after_or_equal:start_date',
                'media_type' => 'sometimes|in:image,video',
                'customer_id' => 'sometimes|nullable|integer',
                'banner_charge' => 'sometimes|numeric|min:0',
                'purchaser_id' => 'sometimes|nullable|integer',
                'purchase_price' => 'sometimes|numeric|min:0',
            ]);

            // Map banner_position to position field
            if (isset($validated['banner_position'])) {
                $validated['position'] = $validated['banner_position'];
                unset($validated['banner_position']);
            }

            // Map banner_charge to purchase_price field
            if (isset($validated['banner_charge'])) {
                $validated['purchase_price'] = $validated['banner_charge'];
                unset($validated['banner_charge']);
            }

            // Map customer_id to purchaser_id (if customer_id is sent, use it as purchaser_id)
            if (isset($validated['customer_id'])) {
                $validated['purchaser_id'] = $validated['customer_id'];
                unset($validated['customer_id']);
            }

            $banner->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => $banner->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Banner update failed', [
                'banner_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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

    public function active(): JsonResponse
    {
        try {
            $banners = Banner::active()
                ->byPriority()
                ->get(['id', 'title', 'description', 'media_url', 'link_url', 'position', 'sort_order']);

            return response()->json([
                'success' => true,
                'data' => $banners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active banners',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get banners for homepage with Jumia-style layout
     */
    public function getHomepageBanners(Request $request): JsonResponse
    {
        try {
            $device = $request->input('device', 'desktop');

            $banners = [
                'hero' => Banner::active()->paid()
                    ->forContext(Banner::CONTEXT_HOMEPAGE)
                    ->forPosition(Banner::POSITION_HERO)
                    ->forDevice($device)
                    ->byPriority()
                    ->limit(3)
                    ->get(),

                'category_row' => Banner::active()->paid()
                    ->forContext(Banner::CONTEXT_HOMEPAGE)
                    ->forPosition(Banner::POSITION_CATEGORY_ROW)
                    ->forDevice($device)
                    ->byPriority()
                    ->limit(8)
                    ->get(),

                'product_promotion' => Banner::active()->paid()
                    ->forContext(Banner::CONTEXT_HOMEPAGE)
                    ->forPosition(Banner::POSITION_PRODUCT_PROMOTION)
                    ->forDevice($device)
                    ->byPriority()
                    ->limit(4)
                    ->get(),

                'sidebar' => Banner::active()->paid()
                    ->forContext(Banner::CONTEXT_HOMEPAGE)
                    ->forPosition(Banner::POSITION_SIDEBAR)
                    ->forDevice($device)
                    ->byPriority()
                    ->limit(2)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $banners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch homepage banners',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get banners for category pages
     */
    public function getCategoryBanners(Request $request): JsonResponse
    {
        try {
            $categoryId = $request->input('category_id');
            $device = $request->input('device', 'desktop');

            $banners = [
                'listing_top' => Banner::active()->paid()
                    ->forContext(Banner::CONTEXT_CATEGORY)
                    ->forPosition(Banner::POSITION_LISTING_TOP)
                    ->forCategory($categoryId)
                    ->forDevice($device)
                    ->byPriority()
                    ->limit(2)
                    ->get(),

                'sidebar' => Banner::active()->paid()
                    ->forContext(Banner::CONTEXT_CATEGORY)
                    ->forPosition(Banner::POSITION_SIDEBAR)
                    ->forCategory($categoryId)
                    ->forDevice($device)
                    ->byPriority()
                    ->limit(3)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $banners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category banners',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get banners for listing detail pages
     */
    public function getListingDetailBanners(Request $request): JsonResponse
    {
        try {
            $categoryId = $request->input('category_id');
            $device = $request->input('device', 'desktop');

            $banners = Banner::active()->paid()
                ->forContext(Banner::CONTEXT_LISTING_DETAIL)
                ->forPosition(Banner::POSITION_DETAIL_SIDEBAR)
                ->forCategory($categoryId)
                ->forDevice($device)
                ->byPriority()
                ->limit(3)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $banners,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch listing detail banners',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get banner configuration options
     */
    public function getConfiguration(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'positions' => Banner::getPositions(),
                    'sizes' => Banner::getSizes(),
                    'contexts' => Banner::getContexts(),
                    'types' => [
                        Banner::TYPE_PROMOTIONAL => 'Promotional',
                        Banner::TYPE_SPONSORED => 'Sponsored',
                        Banner::TYPE_CATEGORY => 'Category',
                        Banner::TYPE_FEATURED => 'Featured',
                        Banner::TYPE_SERVICE => 'Service',
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banner configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}