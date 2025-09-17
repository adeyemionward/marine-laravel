<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Invoice;
use App\Models\User;
use App\Models\EquipmentListing;
use App\Models\SellerApplication;
use App\Models\SubscriptionPlan;
use App\Models\Banner;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialController extends Controller
{
    /**
     * Get financial summary statistics
     */
    public function getFinancialStats(): JsonResponse
    {
        try {
            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth();
            $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
            $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

            // Current month stats
            $currentMonthRevenue = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startOfMonth, $now])
                ->sum('total_amount');

            // Last month stats
            $lastMonthRevenue = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth])
                ->sum('total_amount');

            // Total revenue
            $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');

            // Pending revenue
            $pendingRevenue = Invoice::where('status', 'pending')->sum('total_amount');

            // Overdue revenue
            $overdueRevenue = Invoice::where('status', 'pending')
                ->where('due_date', '<', $now)
                ->sum('total_amount');

            // Active subscriptions count
            $activeSubscriptions = User::whereHas('invoices', function($q) {
                $q->where('status', 'paid')
                  ->where('invoice_type', 'subscription');
            })->count();

            // Revenue by type
            $revenueByType = Invoice::where('status', 'paid')
                ->select('invoice_type', DB::raw('SUM(total_amount) as total'))
                ->groupBy('invoice_type')
                ->get();

            // Top paying customers
            $topCustomers = Invoice::where('status', 'paid')
                ->select('user_id', DB::raw('SUM(total_amount) as total_spent'))
                ->with('user:id,name,email')
                ->groupBy('user_id')
                ->orderByDesc('total_spent')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_revenue' => $totalRevenue,
                        'current_month_revenue' => $currentMonthRevenue,
                        'last_month_revenue' => $lastMonthRevenue,
                        'pending_revenue' => $pendingRevenue,
                        'overdue_revenue' => $overdueRevenue,
                        'active_subscriptions' => $activeSubscriptions,
                        'growth_percentage' => $lastMonthRevenue > 0 ? 
                            round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2) : 0
                    ],
                    'revenue_by_type' => $revenueByType,
                    'top_customers' => $topCustomers,
                    'quick_stats' => [
                        'total_invoices' => Invoice::count(),
                        'paid_invoices' => Invoice::where('status', 'paid')->count(),
                        'pending_invoices' => Invoice::where('status', 'pending')->count(),
                        'overdue_invoices' => Invoice::where('status', 'pending')
                            ->where('due_date', '<', $now)->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch financial stats',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get financial transactions with filters
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $query = Invoice::with(['user:id,name,email', 'subscriptionPlan:id,name']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('type')) {
                $query->where('invoice_type', $request->type);
            }

            if ($request->has('from_date')) {
                $query->where('created_at', '>=', Carbon::parse($request->from_date));
            }

            if ($request->has('to_date')) {
                $query->where('created_at', '<=', Carbon::parse($request->to_date));
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $perPage = $request->get('per_page', 20);
            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get monthly financial trends
     */
    public function getMonthlyTrends(Request $request): JsonResponse
    {
        try {
            $months = $request->get('months', 12);
            $endDate = Carbon::now();
            $startDate = $endDate->copy()->subMonths($months)->startOfMonth();

            $trends = Invoice::where('status', 'paid')
                ->where('paid_at', '>=', $startDate)
                ->select(
                    DB::raw('YEAR(paid_at) as year'),
                    DB::raw('MONTH(paid_at) as month'),
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(total_amount) as revenue'),
                    DB::raw('AVG(total_amount) as avg_transaction')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            // Format the data for chart
            $formattedTrends = [];
            foreach ($trends as $trend) {
                $date = Carbon::create($trend->year, $trend->month, 1);
                $formattedTrends[] = [
                    'month' => $date->format('M Y'),
                    'revenue' => $trend->revenue,
                    'transactions' => $trend->transaction_count,
                    'avg_transaction' => round($trend->avg_transaction, 2)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedTrends
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trends',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get service templates for invoicing
     */
    public function getServiceTemplates(): JsonResponse
    {
        try {
            // Get subscription plans as service templates
            $subscriptionPlans = SubscriptionPlan::where('is_active', true)
                ->select('id', 'name', 'price', 'listing_limit', 'features')
                ->get();

            // Additional service templates (hardcoded for now, can be moved to DB)
            $additionalServices = [
                [
                    'id' => 'featured-listing',
                    'name' => 'Featured Listing',
                    'price' => 50.00,
                    'description' => 'Feature your listing on homepage for 30 days'
                ],
                [
                    'id' => 'premium-support',
                    'name' => 'Premium Support',
                    'price' => 100.00,
                    'description' => 'Priority support for 1 month'
                ],
                [
                    'id' => 'banner-ad',
                    'name' => 'Banner Advertisement',
                    'price' => 200.00,
                    'description' => 'Display banner ad for 30 days'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription_plans' => $subscriptionPlans,
                    'additional_services' => $additionalServices
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
     * Export financial report
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:summary,transactions,revenue',
                'format' => 'required|in:csv,pdf',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date'
            ]);

            // This is a placeholder - implement actual export logic
            return response()->json([
                'success' => true,
                'message' => 'Report generation initiated',
                'data' => [
                    'download_url' => '/api/v1/admin/financial/download-report/' . uniqid()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get revenue breakdown by category
     */
    public function getRevenueBreakdown(): JsonResponse
    {
        try {
            // Revenue by invoice type
            $byType = Invoice::where('status', 'paid')
                ->select('invoice_type', DB::raw('SUM(total_amount) as total'))
                ->groupBy('invoice_type')
                ->get();

            // Revenue by month for current year
            $currentYear = Carbon::now()->year;
            $byMonth = Invoice::where('status', 'paid')
                ->whereYear('paid_at', $currentYear)
                ->select(
                    DB::raw('MONTH(paid_at) as month'),
                    DB::raw('SUM(total_amount) as total')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Revenue by subscription plan
            $byPlan = Invoice::where('status', 'paid')
                ->where('invoice_type', 'subscription')
                ->whereNotNull('plan_id')
                ->select('plan_id', DB::raw('SUM(total_amount) as total'))
                ->with('subscriptionPlan:id,name')
                ->groupBy('plan_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'by_type' => $byType,
                    'by_month' => $byMonth,
                    'by_plan' => $byPlan
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue breakdown',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}