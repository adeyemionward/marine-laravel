<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerSetting;
use App\Models\BannerPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Banner::query();

            // Filter by position if provided
            if ($request->has('position')) {
                $query->where('position', $request->position);
            }

            // Filter by active status if requested
            if ($request->has('active') && $request->active === 'true') {
                $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('start_date')
                          ->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
            }

            // Filter by limit if provided
            $limit = $request->input('limit');

            // Order by priority (higher first) then created_at descending
            $query->orderBy('priority', 'desc')
                  ->orderBy('created_at', 'desc');

            if ($limit) {
                $query->limit($limit);
            }

            $banners = $query->get()->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'description' => $banner->description,
                    'media_type' => $banner->media_type,
                    'media_url' => $banner->media_url,
                    'link_url' => $banner->link_url,
                    'banner_position' => $banner->position,
                    'display_duration' => $banner->display_duration ?? 60,
                    'priority' => $banner->priority,
                    'status' => $banner->status,
                    'start_date' => $banner->start_date,
                    'end_date' => $banner->end_date,
                    'banner_charge' => $banner->purchase_price,
                    'customer_id' => $banner->purchaser_id,
                    'payment_status' => $banner->payment_status,
                    'impression_count' => $banner->impression_count ?? 0,
                    'click_count' => $banner->click_count ?? 0,
                    'button_text' => $banner->button_text ?? 'Shop Now',
                    'created_at' => $banner->created_at,
                    'updated_at' => $banner->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $banners,
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
                'display_duration' => 'nullable|integer|in:15,30,60',
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
                'display_duration' => 'sometimes|nullable|integer|in:15,30,60',
                'priority' => 'sometimes|integer|min:0',
                'status' => 'sometimes|in:active,inactive,expired',
                'start_date' => 'sometimes|nullable|date',
                'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
                'media_type' => 'sometimes|in:image,video',
                'customer_id' => 'sometimes|nullable|integer',
                'banner_charge' => 'sometimes|nullable|numeric|min:0',
                'purchaser_id' => 'sometimes|nullable|integer',
                'purchase_price' => 'sometimes|nullable|numeric|min:0',
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

    /**
     * Get banner settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = BannerSetting::all();

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banner settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update banner settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required|integer|min:1000|max:30000',
            ]);

            foreach ($validated['settings'] as $setting) {
                BannerSetting::set($setting['key'], $setting['value']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Banner settings updated successfully',
                'data' => BannerSetting::all(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get banner pricing configuration
     */
    public function getPricing(): JsonResponse
    {
        try {
            // Get all active pricing or create defaults if none exist
            $pricing = BannerPricing::active()->get();

            if ($pricing->isEmpty()) {
                // Create default pricing if none exists
                $defaults = [
                    ['banner_type' => 'hero', 'base_price' => 50000, 'duration_type' => 'weekly', 'duration_value' => 1],
                    ['banner_type' => 'sidebar', 'base_price' => 25000, 'duration_type' => 'weekly', 'duration_value' => 1],
                    ['banner_type' => 'category', 'base_price' => 30000, 'duration_type' => 'weekly', 'duration_value' => 1],
                    ['banner_type' => 'footer', 'base_price' => 15000, 'duration_type' => 'weekly', 'duration_value' => 1],
                ];

                foreach ($defaults as $default) {
                    BannerPricing::create(array_merge($default, [
                        'is_active' => true,
                        'premium_multiplier' => 1.5,
                    ]));
                }

                $pricing = BannerPricing::active()->get();
            }

            // Format pricing for frontend (hero, sidebar, category, footer)
            $formattedPricing = [
                'hero' => 50000,
                'sidebar' => 25000,
                'category' => 30000,
                'footer' => 15000,
            ];

            foreach ($pricing as $item) {
                $formattedPricing[$item->banner_type] = (float) $item->base_price;
            }

            return response()->json([
                'success' => true,
                'data' => $formattedPricing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banner pricing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update banner pricing
     */
    public function updatePricing(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pricing' => 'required|array',
                'pricing.hero' => 'required|numeric|min:0',
                'pricing.sidebar' => 'required|numeric|min:0',
                // 'pricing.category' => 'required|numeric|min:0',
                'pricing.footer' => 'required|numeric|min:0',
                'pricing.bottom_middle' => 'required|numeric|min:0',
                'pricing.bottom_left' => 'required|numeric|min:0',
                'pricing.bottom_right' => 'required|numeric|min:0',
                'pricing.sidebar_right' => 'required|numeric|min:0',
                'pricing.sidebar_left' => 'required|numeric|min:0',
                'pricing.middle' => 'required|numeric|min:0',
            ]);

            $pricing = $validated['pricing'];

            foreach ($pricing as $type => $price) {
                BannerPricing::updateOrCreate(
                    ['banner_type' => $type],
                    [
                        'base_price' => $price,
                        'duration_type' => 'weekly',
                        'duration_value' => 1,
                        'is_active' => true,
                        'premium_multiplier' => 1.5,
                    ]
                );
            }

            // Get updated pricing
            $updatedPricing = BannerPricing::active()->get();
            $formattedPricing = [
                'hero' => 50000,
                'sidebar' => 25000,
                'category' => 30000,
                'footer' => 15000,
                'bottom_middle' => 15000,
                'bottom_left' => 15000,
                'bottom_right' => 15000,
            ];

            foreach ($updatedPricing as $item) {
                $formattedPricing[$item->banner_type] = (float) $item->base_price;
            }

            return response()->json([
                'success' => true,
                'message' => 'Banner pricing updated successfully',
                'data' => $formattedPricing,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Banner pricing update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner pricing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
