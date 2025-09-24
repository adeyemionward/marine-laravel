<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FinancialTransactionController extends Controller
{
    /**
     * Get financial summary
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->get('date_range', '30'); // Default 30 days
            $startDate = Carbon::now()->subDays($dateRange)->startOfDay();

            $transactions = FinancialTransaction::where('transaction_date', '>=', $startDate);

            $summary = [
                'total_income' => $transactions->clone()->where('transaction_type', 'income')->sum('amount'),
                'total_expenses' => $transactions->clone()->where('transaction_type', 'expense')->sum('amount'),
                'transaction_count' => $transactions->count(),
                'income_by_category' => $transactions->clone()
                    ->where('transaction_type', 'income')
                    ->select('category', DB::raw('SUM(amount) as total'))
                    ->groupBy('category')
                    ->pluck('total', 'category'),
                'expense_by_category' => $transactions->clone()
                    ->where('transaction_type', 'expense')
                    ->select('category', DB::raw('SUM(amount) as total'))
                    ->groupBy('category')
                    ->pluck('total', 'category'),
            ];

            $summary['net_profit'] = $summary['total_income'] - $summary['total_expenses'];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get financial summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions with filters
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $query = FinancialTransaction::with(['user', 'subscription', 'equipmentListing', 'bannerPurchase', 'recordedBy']);

            // Apply filters
            if ($request->filled('type')) {
                $query->where('transaction_type', $request->type);
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('transaction_date', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('transaction_reference', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            // Order by transaction date desc
            $query->orderBy('transaction_date', 'desc');

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new transaction
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'transaction_type' => 'required|in:income,expense',
                'category' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:1000',
                'notes' => 'nullable|string|max:2000',
                'transaction_date' => 'required|date',
                'payment_method' => 'nullable|string|max:100',
                'payment_reference' => 'nullable|string|max:100',
                'user_id' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $transaction = FinancialTransaction::create([
                'transaction_type' => $request->transaction_type,
                'category' => $request->category,
                'amount' => $request->amount,
                'description' => $request->description,
                'notes' => $request->notes,
                'transaction_date' => $request->transaction_date,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'user_id' => $request->user_id,
                'recorded_by' => auth()->id(),
                'payment_status' => 'completed'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction->load(['user', 'recordedBy'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a transaction
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $transaction = FinancialTransaction::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'transaction_type' => 'required|in:income,expense',
                'category' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:1000',
                'notes' => 'nullable|string|max:2000',
                'transaction_date' => 'required|date',
                'payment_method' => 'nullable|string|max:100',
                'payment_reference' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $transaction->update($request->only([
                'transaction_type', 'category', 'amount', 'description',
                'notes', 'transaction_date', 'payment_method', 'payment_reference'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully',
                'data' => $transaction->load(['user', 'recordedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a transaction
     */
    public function destroy($id): JsonResponse
    {
        try {
            $transaction = FinancialTransaction::findOrFail($id);
            $transaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly trends
     */
    public function getMonthlyTrends(Request $request): JsonResponse
    {
        try {
            $months = $request->get('months', 6);
            $startDate = Carbon::now()->subMonths($months)->startOfMonth();

            $trends = FinancialTransaction::select(
                DB::raw('YEAR(transaction_date) as year'),
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE 0 END) as income'),
                DB::raw('SUM(CASE WHEN transaction_type = "expense" THEN amount ELSE 0 END) as expenses')
            )
            ->where('transaction_date', '>=', $startDate)
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                    'income' => floatval($item->income),
                    'expenses' => floatval($item->expenses),
                    'profit' => floatval($item->income - $item->expenses)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $trends
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get monthly trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category statistics
     */
    public function getCategoryStats(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->get('date_range', '30');
            $startDate = Carbon::now()->subDays($dateRange)->startOfDay();

            $stats = FinancialTransaction::where('transaction_date', '>=', $startDate)
                ->select(
                    'category',
                    'transaction_type',
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('AVG(amount) as avg_amount')
                )
                ->groupBy('category', 'transaction_type')
                ->orderBy('total_amount', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get category stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reconcile transactions
     */
    public function reconcile(Request $request): JsonResponse
    {
        try {
            $transactionIds = $request->get('transaction_ids', []);

            if (empty($transactionIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No transactions selected for reconciliation'
                ], 422);
            }

            $updated = FinancialTransaction::whereIn('id', $transactionIds)
                ->update([
                    'is_reconciled' => true,
                    'reconciled_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully reconciled {$updated} transactions"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reconcile transactions: ' . $e->getMessage()
            ], 500);
        }
    }
}
