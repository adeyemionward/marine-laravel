<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PlatformSettingsController extends Controller
{
    /**
     * Get listing pricing configuration
     */
    public function getListingPricing(): JsonResponse
    {
        try {
            // Get pricing from cache or database
            $pricing = Cache::remember('platform_listing_pricing', 3600, function () {
                return [
                    // Featured Listings
                    'featured_listing_category' => (float) $this->getSetting('pricing_featured_listing_category', 30000),
                    'featured_listing_homepage' => (float) $this->getSetting('pricing_featured_listing_homepage', 50000),
                    // Priority Listing
                    'priority_listing' => (float) $this->getSetting('pricing_priority_listing', 30000),
                    'basic_listing' => (float) $this->getSetting('pricing_basic_listing', 0),
                    // Listing Promotions (daily rates)
                    'promotion_boost' => (float) $this->getSetting('pricing_promotion_boost', 3000),
                    'promotion_spotlight' => (float) $this->getSetting('pricing_promotion_spotlight', 5000),
                    'promotion_super_boost' => (float) $this->getSetting('pricing_promotion_super_boost', 8000),
                    // Verification Badges (one-time fees)
                    'verification_business' => (float) $this->getSetting('pricing_verification_business', 25000),
                    'verification_identity' => (float) $this->getSetting('pricing_verification_identity', 10000),
                    'verification_premium' => (float) $this->getSetting('pricing_verification_premium', 50000),
                    // Tax
                    'tax_rate' => (float) $this->getSetting('pricing_tax_rate', 7.5)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $pricing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve listing pricing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update listing pricing configuration
     */
    public function updateListingPricing(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'pricing.featured_listing_category' => 'required|numeric|min:0',
                'pricing.featured_listing_homepage' => 'required|numeric|min:0',
                'pricing.priority_listing' => 'required|numeric|min:0',
                'pricing.basic_listing' => 'nullable|numeric|min:0',
                'pricing.promotion_boost' => 'required|numeric|min:0',
                'pricing.promotion_spotlight' => 'required|numeric|min:0',
                'pricing.promotion_super_boost' => 'required|numeric|min:0',
                'pricing.verification_business' => 'required|numeric|min:0',
                'pricing.verification_identity' => 'required|numeric|min:0',
                'pricing.verification_premium' => 'required|numeric|min:0',
                'pricing.tax_rate' => 'required|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pricing = $request->input('pricing');

            // Store in configuration file or database
            // For now, we'll use a settings table approach
            $this->saveSetting('pricing_featured_listing_category', $pricing['featured_listing_category']);
            $this->saveSetting('pricing_featured_listing_homepage', $pricing['featured_listing_homepage']);
            $this->saveSetting('pricing_priority_listing', $pricing['priority_listing']);
            $this->saveSetting('pricing_basic_listing', $pricing['basic_listing'] ?? 0);
            $this->saveSetting('pricing_promotion_boost', $pricing['promotion_boost']);
            $this->saveSetting('pricing_promotion_spotlight', $pricing['promotion_spotlight']);
            $this->saveSetting('pricing_promotion_super_boost', $pricing['promotion_super_boost']);
            $this->saveSetting('pricing_verification_business', $pricing['verification_business']);
            $this->saveSetting('pricing_verification_identity', $pricing['verification_identity']);
            $this->saveSetting('pricing_verification_premium', $pricing['verification_premium']);
            $this->saveSetting('pricing_tax_rate', $pricing['tax_rate']);

            // Clear cache
            Cache::forget('platform_listing_pricing');

            return response()->json([
                'success' => true,
                'message' => 'Listing pricing updated successfully',
                'data' => [
                    'featured_listing_category' => (float) $pricing['featured_listing_category'],
                    'featured_listing_homepage' => (float) $pricing['featured_listing_homepage'],
                    'priority_listing' => (float) $pricing['priority_listing'],
                    'basic_listing' => (float) ($pricing['basic_listing'] ?? 0),
                    'promotion_boost' => (float) $pricing['promotion_boost'],
                    'promotion_spotlight' => (float) $pricing['promotion_spotlight'],
                    'promotion_super_boost' => (float) $pricing['promotion_super_boost'],
                    'verification_business' => (float) $pricing['verification_business'],
                    'verification_identity' => (float) $pricing['verification_identity'],
                    'verification_premium' => (float) $pricing['verification_premium'],
                    'tax_rate' => (float) $pricing['tax_rate']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing pricing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bank account details
     */
    public function getBankDetails(): JsonResponse
    {
        try {
            // Get bank details from cache or database
            $bankDetails = Cache::remember('platform_bank_details', 3600, function () {
                return [
                    'bank_name' => $this->getSetting('bank_name', 'First Bank of Nigeria'),
                    'account_number' => $this->getSetting('account_number', '3052341234'),
                    'account_name' => $this->getSetting('account_name', 'MarineNG Limited')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $bankDetails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve bank details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update bank account details
     */
    public function updateBankDetails(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'bank_details.bank_name' => 'required|string|max:255',
                'bank_details.account_number' => 'required|string|max:20',
                'bank_details.account_name' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bankDetails = $request->input('bank_details');

            // Store in database
            $this->saveSetting('bank_name', $bankDetails['bank_name']);
            $this->saveSetting('account_number', $bankDetails['account_number']);
            $this->saveSetting('account_name', $bankDetails['account_name']);

            // Clear cache
            Cache::forget('platform_bank_details');

            return response()->json([
                'success' => true,
                'message' => 'Bank details updated successfully',
                'data' => [
                    'bank_name' => $bankDetails['bank_name'],
                    'account_number' => $bankDetails['account_number'],
                    'account_name' => $bankDetails['account_name']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bank details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate invoice with tax
     */
    public function calculateInvoice(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'subtotal' => 'required|numeric|min:0',
                'tax_rate' => 'nullable|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $subtotal = $request->input('subtotal');
            $taxRate = $request->input('tax_rate') ?? $this->getSetting('pricing_tax_rate', 7.5);

            $taxAmount = ($subtotal * $taxRate) / 100;
            $total = $subtotal + $taxAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => (float) $subtotal,
                    'tax_rate' => (float) $taxRate,
                    'tax_amount' => (float) $taxAmount,
                    'total' => (float) $total
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save a setting to database
     */
    private function saveSetting(string $key, $value): void
    {
        \DB::table('platform_settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $value,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Get a setting from database
     */
    private function getSetting(string $key, $default = null)
    {
        $setting = \DB::table('platform_settings')->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
