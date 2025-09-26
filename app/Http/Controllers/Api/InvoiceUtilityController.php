<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\SubscriptionPlan;

class InvoiceUtilityController extends Controller
{
    /**
     * Get users formatted for invoice customer dropdown
     */
    public function getUsersForInvoiceDropdown(Request $request): JsonResponse
    {
        try {
            $query = User::select('id', 'name', 'email')
                ->with('profile:user_id,company_name,phone_number');

            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('profile', function($q) use ($search) {
                          $q->where('company_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('role')) {
                $query->whereHas('role', function($q) use ($request) {
                    $q->where('name', $request->get('role'));
                });
            }

            if ($request->has('verified_only') && $request->boolean('verified_only')) {
                $query->whereNotNull('email_verified_at');
            }

            $users = $query->orderBy('name')
                ->limit($request->get('limit', 100))
                ->get()
                ->map(function($user) {
                    $displayName = $user->name;
                    $displayEmail = $user->email;

                    if ($user->profile && $user->profile->company_name) {
                        $displayName = $user->profile->company_name . ' (' . $user->name . ')';
                    }

                    return [
                        'value' => $user->id,
                        'label' => $displayName,
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $displayEmail,
                        'company' => $user->profile?->company_name,
                        'phone' => $user->profile?->phone_number,
                        'display_name' => $displayName,
                        'search_text' => strtolower($user->name . ' ' . $user->email . ' ' . ($user->profile?->company_name ?? ''))
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users,
                'total' => $users->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users for dropdown',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get service templates for invoice creation
     */
    public function getServiceTemplates(): JsonResponse
    {
        try {
            // Get subscription plans
            $subscriptionPlans = SubscriptionPlan::where('is_active', true)
                ->select('id', 'name', 'price', 'listing_limit', 'features', 'description')
                ->get()
                ->map(function($plan) {
                    return [
                        'value' => "subscription_plan_{$plan->id}",
                        'label' => $plan->name . ' - ₦' . number_format($plan->price, 2),
                        'type' => 'subscription',
                        'price' => $plan->price,
                        'description' => $plan->description ?? "Subscription plan with {$plan->listing_limit} listings",
                        'features' => $plan->features
                    ];
                });

            // Additional service templates
            $additionalServices = collect([
                [
                    'value' => 'featured_listing',
                    'label' => 'Featured Listing - ₦50.00',
                    'type' => 'service',
                    'price' => 50.00,
                    'description' => 'Feature your listing on homepage for 30 days'
                ],
                [
                    'value' => 'premium_support',
                    'label' => 'Premium Support - ₦100.00',
                    'type' => 'service',
                    'price' => 100.00,
                    'description' => 'Priority support for 1 month'
                ],
                [
                    'value' => 'banner_ad',
                    'label' => 'Banner Advertisement - ₦200.00',
                    'type' => 'service',
                    'price' => 200.00,
                    'description' => 'Display banner ad for 30 days'
                ],
                [
                    'value' => 'seller_verification',
                    'label' => 'Seller Verification - ₦500.00',
                    'type' => 'service',
                    'price' => 500.00,
                    'description' => 'Seller verification and badge'
                ],
                [
                    'value' => 'consultation',
                    'label' => 'Business Consultation - ₦1,000.00',
                    'type' => 'service',
                    'price' => 1000.00,
                    'description' => 'One-on-one business consultation session'
                ],
                [
                    'value' => 'custom_service',
                    'label' => 'Custom Service - Custom Price',
                    'type' => 'custom',
                    'price' => 0.00,
                    'description' => 'Custom service with flexible pricing'
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription_plans' => $subscriptionPlans,
                    'additional_services' => $additionalServices,
                    'all_services' => $subscriptionPlans->concat($additionalServices)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service templates',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get invoice statistics for dashboard
     */
    public function getInvoiceStats(): JsonResponse
    {
        try {
            $totalInvoices = \App\Models\Invoice::count();
            $paidInvoices = \App\Models\Invoice::where('status', 'paid')->count();
            $pendingInvoices = \App\Models\Invoice::where('status', 'pending')->count();
            $overdueInvoices = \App\Models\Invoice::where('status', 'pending')
                ->where('due_date', '<', now())
                ->count();

            $totalRevenue = \App\Models\Invoice::where('status', 'paid')
                ->sum('total_amount');

            $thisMonthRevenue = \App\Models\Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [now()->startOfMonth(), now()])
                ->sum('total_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_invoices' => $totalInvoices,
                    'paid_invoices' => $paidInvoices,
                    'pending_invoices' => $pendingInvoices,
                    'overdue_invoices' => $overdueInvoices,
                    'total_revenue' => $totalRevenue,
                    'this_month_revenue' => $thisMonthRevenue,
                    'collection_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoice statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}