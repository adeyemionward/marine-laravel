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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class FinancialController extends Controller
{
    /**
     * Get financial summary statistics
     */
    public function getFinancialStats(): JsonResponse
    {
        try {
            $this->logFinancialOperation('get_financial_stats', [
                'user_role' => auth()->user()?->role ?? 'unknown'
            ]);
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
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to fetch financial stats', 'FINANCIAL_STATS_ERROR', [
                'period' => 'current_vs_last_month',
                'stats_requested' => [
                    'current_month_revenue',
                    'last_month_revenue',
                    'total_revenue',
                    'pending_revenue',
                    'overdue_revenue'
                ]
            ]);
        }
    }

    /**
     * Get financial transactions with filters
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $this->logFinancialOperation('get_transactions', [
                'filters' => $request->only(['type', 'status', 'start_date', 'end_date', 'user_id']),
                'page' => $request->get('page', 1),
                'per_page' => $request->get('per_page', 15)
            ]);
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
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to fetch transactions', 'FINANCIAL_TRANSACTIONS_ERROR', [
                'filters' => request()->only(['type', 'status', 'start_date', 'end_date', 'user_id']),
                'page' => request()->get('page', 1),
                'per_page' => request()->get('per_page', 15)
            ]);
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
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to fetch trends', 'FINANCIAL_TRENDS_ERROR', [
                'period' => request()->get('period', '6months'),
                'type' => request()->get('type', 'revenue')
            ]);
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
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to fetch service templates', 'SERVICE_TEMPLATES_ERROR');
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
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to export report', 'REPORT_EXPORT_ERROR', [
                'format' => request()->get('format', 'csv'),
                'type' => request()->get('type', 'summary')
            ]);
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
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to fetch revenue breakdown', 'REVENUE_BREAKDOWN_ERROR', [
                'period' => request()->get('period', 'month'),
                'breakdown_type' => 'category_based'
            ]);
        }
    }

    /**
     * Get revenue summary
     */
    public function getRevenueSummary(Request $request): JsonResponse
    {
        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

            $totalRevenue = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->sum('total_amount');

            $invoiceCount = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->count();

            $avgInvoiceValue = $invoiceCount > 0 ? $totalRevenue / $invoiceCount : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => $totalRevenue,
                    'invoice_count' => $invoiceCount,
                    'avg_invoice_value' => round($avgInvoiceValue, 2),
                    'period' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to fetch revenue summary', 'REVENUE_SUMMARY_ERROR', [
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date')
            ]);
        }
    }

    /**
     * Get expense summary
     */
    public function getExpenseSummary(Request $request): JsonResponse
    {
        try {
            // For now, return mock data since we don't have an expenses table
            // In a real implementation, you would have an expenses table/model
            return response()->json([
                'success' => true,
                'data' => [
                    'total_expenses' => 0,
                    'expense_count' => 0,
                    'categories' => [],
                    'period' => [
                        'start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                        'end' => Carbon::now()->endOfMonth()->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to fetch expense summary', 'EXPENSE_SUMMARY_ERROR');
        }
    }

    /**
     * Create expense
     */
    public function createExpense(Request $request): JsonResponse
    {
        try {
            // For now, return success since we don't have an expenses table
            // In a real implementation, you would validate and create expense record
            return response()->json([
                'success' => true,
                'message' => 'Expense feature coming soon',
                'data' => null
            ]);
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to create expense', 'EXPENSE_CREATE_ERROR', [
                'expense_data' => request()->only(['amount', 'category', 'description'])
            ]);
        }
    }

    /**
     * Update expense
     */
    public function updateExpense(Request $request, $id): JsonResponse
    {
        try {
            // For now, return success since we don't have an expenses table
            return response()->json([
                'success' => true,
                'message' => 'Expense feature coming soon',
                'data' => null
            ]);
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to update expense', 'EXPENSE_UPDATE_ERROR', [
                'expense_id' => request()->route('id'),
                'update_data' => request()->only(['amount', 'category', 'description'])
            ]);
        }
    }

    /**
     * Delete expense
     */
    public function deleteExpense($id): JsonResponse
    {
        try {
            // For now, return success since we don't have an expenses table
            return response()->json([
                'success' => true,
                'message' => 'Expense feature coming soon'
            ]);
        } catch (Throwable $e) {
            return $this->handleError($e, 'Failed to delete expense', 'EXPENSE_DELETE_ERROR', [
                'expense_id' => request()->route('id')
            ]);
        }
    }

    /**
     * Export financial report
     */
    public function exportFinancialReport(Request $request)
    {
        try {
            // For now, return a simple CSV response
            $csvData = "Date,Type,Amount,Description\n";
            $csvData .= date('Y-m-d') . ",Revenue,0,Sample revenue\n";
            $csvData .= date('Y-m-d') . ",Expense,0,Sample expense\n";

            return response($csvData, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="financial_report.csv"');
        } catch (\Exception $e) {
            return $this->handleError($e, 'Failed to export financial report', 'FINANCIAL_EXPORT_ERROR');
        }
    }

    /**
     * Handle errors consistently with proper logging
     */
    private function handleError(Throwable $e, string $userMessage, string $errorCode = 'FINANCIAL_ERROR', array $context = []): JsonResponse
    {
        // Generate unique error ID for tracking
        $errorId = uniqid('ERR_');

        // Prepare context for logging
        $logContext = array_merge($context, [
            'error_id' => $errorId,
            'error_code' => $errorCode,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'parameters' => request()->all(),
            'exception_class' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Log the error with full context
        Log::channel('financial')->error($userMessage, $logContext);

        // Also log to the general log for monitoring
        Log::error("Financial Module Error [{$errorId}]: {$userMessage}", [
            'error_id' => $errorId,
            'exception' => $e->getMessage(),
            'user_id' => auth()->id(),
            'context' => $errorCode
        ]);

        // Return structured error response
        return response()->json([
            'success' => false,
            'message' => $userMessage,
            'error_id' => $errorId,
            'error_code' => $errorCode,
            'error' => config('app.debug') ? [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ] : 'Internal server error'
        ], 500);
    }

    /**
     * Log financial operations for audit trail
     */
    private function logFinancialOperation(string $operation, array $data = [], string $level = 'info'): void
    {
        $context = [
            'operation' => $operation,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toISOString(),
            'data' => $data
        ];

        Log::channel('financial')->{$level}("Financial Operation: {$operation}", $context);
    }
}