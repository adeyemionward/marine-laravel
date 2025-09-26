<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Expense::with(['creator', 'approver'])
                ->orderBy('expense_date', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('from_date')) {
                $query->where('expense_date', '>=', Carbon::parse($request->from_date));
            }

            if ($request->has('to_date')) {
                $query->where('expense_date', '<=', Carbon::parse($request->to_date));
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('vendor_name', 'like', "%{$search}%")
                      ->orWhere('expense_number', 'like', "%{$search}%");
                });
            }

            $expenses = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $expenses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expenses',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'category' => 'required|string|in:' . implode(',', Expense::getCategoriesList()),
                'subcategory' => 'nullable|string',
                'description' => 'required|string|max:1000',
                'expense_date' => 'required|date',
                'vendor_name' => 'nullable|string|max:255',
                'payment_method' => 'nullable|string|in:' . implode(',', Expense::$paymentMethods),
                'receipt_url' => 'nullable|url',
                'notes' => 'nullable|string|max:1000',
                'is_recurring' => 'nullable|boolean',
                'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:monthly,quarterly,yearly',
                'recurring_end_date' => 'nullable|date|after:expense_date',
                'tax_amount' => 'nullable|numeric|min:0',
                'reference_number' => 'nullable|string|max:255'
            ]);

            $validated['created_by'] = Auth::id();

            $expense = Expense::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully',
                'data' => $expense->load(['creator', 'approver'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified expense
     */
    public function show($id): JsonResponse
    {
        try {
            $expense = Expense::with(['creator', 'approver'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 404);
        }
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $expense = Expense::findOrFail($id);

            // Check if expense is already approved or paid
            if (in_array($expense->status, ['approved', 'paid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update expense that is already approved or paid'
                ], 400);
            }

            $validated = $request->validate([
                'amount' => 'sometimes|numeric|min:0.01',
                'category' => 'sometimes|string|in:' . implode(',', Expense::getCategoriesList()),
                'subcategory' => 'nullable|string',
                'description' => 'sometimes|string|max:1000',
                'expense_date' => 'sometimes|date',
                'vendor_name' => 'nullable|string|max:255',
                'payment_method' => 'nullable|string|in:' . implode(',', Expense::$paymentMethods),
                'receipt_url' => 'nullable|url',
                'notes' => 'nullable|string|max:1000',
                'is_recurring' => 'nullable|boolean',
                'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:monthly,quarterly,yearly',
                'recurring_end_date' => 'nullable|date|after:expense_date',
                'tax_amount' => 'nullable|numeric|min:0',
                'reference_number' => 'nullable|string|max:255'
            ]);

            $expense->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => $expense->load(['creator', 'approver'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy($id): JsonResponse
    {
        try {
            $expense = Expense::findOrFail($id);

            // Check if expense is already approved or paid
            if (in_array($expense->status, ['approved', 'paid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete expense that is already approved or paid'
                ], 400);
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Approve expense
     */
    public function approve(Request $request, $id): JsonResponse
    {
        try {
            $expense = Expense::findOrFail($id);

            if ($expense->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending expenses can be approved'
                ], 400);
            }

            $expense->approve(Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Expense approved successfully',
                'data' => $expense->load(['creator', 'approver'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve expense',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reject expense
     */
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            $expense = Expense::findOrFail($id);

            if ($expense->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending expenses can be rejected'
                ], 400);
            }

            $expense->reject(Auth::user(), $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Expense rejected successfully',
                'data' => $expense->load(['creator', 'approver'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject expense',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Mark expense as paid
     */
    public function markAsPaid($id): JsonResponse
    {
        try {
            $expense = Expense::findOrFail($id);

            if ($expense->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved expenses can be marked as paid'
                ], 400);
            }

            $expense->markAsPaid();

            return response()->json([
                'success' => true,
                'message' => 'Expense marked as paid successfully',
                'data' => $expense->load(['creator', 'approver'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark expense as paid',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get expense categories and subcategories
     */
    public function getCategories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'categories' => Expense::$categories,
                'payment_methods' => Expense::$paymentMethods
            ]
        ]);
    }

    /**
     * Get expense statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

            $stats = [
                'total_expenses' => Expense::byDateRange($startDate, $endDate)
                    ->whereIn('status', ['approved', 'paid'])
                    ->sum('amount'),
                'pending_expenses' => Expense::pending()->sum('amount'),
                'approved_expenses' => Expense::approved()->sum('amount'),
                'expense_count' => Expense::byDateRange($startDate, $endDate)
                    ->whereIn('status', ['approved', 'paid'])
                    ->count(),
                'pending_count' => Expense::pending()->count(),
                'approved_count' => Expense::approved()->count(),
                'by_category' => Expense::getExpensesByCategory($startDate, $endDate),
                'avg_expense' => Expense::byDateRange($startDate, $endDate)
                    ->whereIn('status', ['approved', 'paid'])
                    ->avg('amount') ?: 0
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expense statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}