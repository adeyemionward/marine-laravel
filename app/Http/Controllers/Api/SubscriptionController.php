<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        try {
            $plans = SubscriptionPlan::active()
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $plans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'tier' => $plan->tier,
                        'description' => $plan->description,
                        'price' => $plan->price,
                        'formatted_price' => $plan->getFormattedPriceAttribute(),
                        'billing_cycle' => $plan->billing_cycle,
                        'features' => $plan->features,
                        'limits' => $plan->limits,
                        'max_listings' => $plan->max_listings,
                        'max_images_per_listing' => $plan->max_images_per_listing,
                        'priority_support' => $plan->priority_support,
                        'analytics_access' => $plan->analytics_access,
                        'custom_branding' => $plan->custom_branding,
                        'sort_order' => $plan->sort_order,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription plans',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function subscribe(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:subscription_plans,id',
                'payment_method_id' => 'sometimes|string',
                'billing_cycle' => 'sometimes|string|in:monthly,yearly',
            ]);

            $userId = Auth::user()->profile->id;
            $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

            // Check if user already has an active subscription
            $activeSubscription = Subscription::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if ($activeSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has an active subscription',
                ], 400);
            }

            // Create new subscription
            $subscription = Subscription::create([
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addMonth(), // Default to 1 month
                'auto_renew' => true,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription activated successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'plan_name' => $plan->name,
                    'status' => $subscription->status,
                    'expires_at' => $subscription->expires_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;

            $subscription = Subscription::where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found',
                ], 404);
            }

            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'auto_renew' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'cancelled_at' => $subscription->cancelled_at,
                    'expires_at' => $subscription->expires_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function usage(): JsonResponse
    {
        try {
            $userId = Auth::user()->profile->id;

            $subscription = Subscription::where('user_id', $userId)
                ->with('plan')
                ->where('status', 'active')
                ->first();

            // Get current usage count
            $listingsCount = \App\Models\EquipmentListing::where('seller_id', $userId)->count();

            if (!$subscription) {
                // Return freemium tier limits for users without subscription
                return response()->json([
                    'success' => true,
                    'data' => [
                        'plan_name' => 'Freemium',
                        'plan_limits' => [
                            'max_listings' => 5,
                            'max_images_per_listing' => 5,
                        ],
                        'current_usage' => [
                            'listings_count' => $listingsCount,
                            'listings_remaining' => max(0, 5 - $listingsCount),
                        ],
                        'subscription_status' => [
                            'status' => 'freemium',
                            'expires_at' => null,
                            'days_remaining' => null,
                            'auto_renew' => false,
                        ],
                    ],
                ]);
            }

            // Return usage for users with active subscription
            return response()->json([
                'success' => true,
                'data' => [
                    'plan_name' => $subscription->plan->name,
                    'plan_limits' => [
                        'max_listings' => $subscription->plan->max_listings,
                        'max_images_per_listing' => $subscription->plan->max_images_per_listing,
                    ],
                    'current_usage' => [
                        'listings_count' => $listingsCount,
                        'listings_remaining' => $subscription->plan->max_listings === -1 ? 
                            'unlimited' : max(0, $subscription->plan->max_listings - $listingsCount),
                    ],
                    'subscription_status' => [
                        'status' => $subscription->status,
                        'expires_at' => $subscription->expires_at,
                        'days_remaining' => $subscription->expires_at ? 
                            max(0, $subscription->expires_at->diffInDays(now())) : null,
                        'auto_renew' => $subscription->auto_renew,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch usage data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}