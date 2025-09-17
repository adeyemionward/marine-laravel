<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Invoice;
use App\Models\EquipmentListing;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerSupplierController extends Controller
{
    /**
     * Get all customers (users who have made purchases or have listings)
     */
    public function getCustomers(Request $request): JsonResponse
    {
        try {
            $query = User::with(['userProfile', 'role'])
                ->withCount(['equipmentListings', 'invoices']);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('has_purchases') && $request->has_purchases == 'true') {
                $query->whereHas('invoices', function($q) {
                    $q->where('status', 'paid');
                });
            }

            // Get customers with their statistics
            $customers = $query->select('users.*')
                ->selectSub(function($q) {
                    $q->from('invoices')
                      ->whereColumn('invoices.user_id', 'users.id')
                      ->where('status', 'paid')
                      ->selectRaw('SUM(total_amount)');
                }, 'total_spent')
                ->selectSub(function($q) {
                    $q->from('invoices')
                      ->whereColumn('invoices.user_id', 'users.id')
                      ->where('status', 'paid')
                      ->selectRaw('COUNT(*)');
                }, 'purchase_count')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all suppliers (users who are sellers or have active listings)
     */
    public function getSuppliers(Request $request): JsonResponse
    {
        try {
            $query = User::with(['userProfile', 'role', 'sellerProfile'])
                ->whereHas('role', function($q) {
                    $q->where('name', 'seller');
                })
                ->orWhereHas('equipmentListings');

            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('sellerProfile', function($q) use ($search) {
                          $q->where('business_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('verified_only') && $request->verified_only == 'true') {
                $query->whereHas('sellerProfile', function($q) {
                    $q->where('is_verified', true);
                });
            }

            // Get suppliers with their statistics
            $suppliers = $query->select('users.*')
                ->selectSub(function($q) {
                    $q->from('equipment_listings')
                      ->whereColumn('equipment_listings.user_id', 'users.id')
                      ->where('status', 'active')
                      ->selectRaw('COUNT(*)');
                }, 'active_listings')
                ->selectSub(function($q) {
                    $q->from('equipment_listings')
                      ->whereColumn('equipment_listings.user_id', 'users.id')
                      ->where('status', 'sold')
                      ->selectRaw('COUNT(*)');
                }, 'sold_listings')
                ->selectSub(function($q) {
                    $q->from('invoices')
                      ->join('seller_applications', 'invoices.seller_application_id', '=', 'seller_applications.id')
                      ->whereColumn('seller_applications.user_id', 'users.id')
                      ->where('invoices.status', 'paid')
                      ->selectRaw('SUM(total_amount)');
                }, 'total_revenue')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suppliers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get customer details with transaction history
     */
    public function getCustomerDetails($id): JsonResponse
    {
        try {
            $customer = User::with(['userProfile', 'role', 'equipmentListings', 'invoices'])
                ->findOrFail($id);

            // Get transaction history
            $transactions = Invoice::where('user_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get statistics
            $stats = [
                'total_spent' => Invoice::where('user_id', $id)
                    ->where('status', 'paid')
                    ->sum('total_amount'),
                'total_purchases' => Invoice::where('user_id', $id)
                    ->where('status', 'paid')
                    ->count(),
                'active_listings' => EquipmentListing::where('user_id', $id)
                    ->where('status', 'active')
                    ->count(),
                'member_since' => $customer->created_at->format('Y-m-d'),
                'last_activity' => $customer->updated_at->diffForHumans()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $customer,
                    'transactions' => $transactions,
                    'statistics' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get supplier details with listing history
     */
    public function getSupplierDetails($id): JsonResponse
    {
        try {
            $supplier = User::with(['userProfile', 'role', 'sellerProfile', 'equipmentListings'])
                ->findOrFail($id);

            // Get listing history
            $listings = EquipmentListing::where('user_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get statistics
            $stats = [
                'total_listings' => EquipmentListing::where('user_id', $id)->count(),
                'active_listings' => EquipmentListing::where('user_id', $id)
                    ->where('status', 'active')
                    ->count(),
                'sold_listings' => EquipmentListing::where('user_id', $id)
                    ->where('status', 'sold')
                    ->count(),
                'total_revenue' => Invoice::join('seller_applications', 'invoices.seller_application_id', '=', 'seller_applications.id')
                    ->where('seller_applications.user_id', $id)
                    ->where('invoices.status', 'paid')
                    ->sum('invoices.total_amount'),
                'member_since' => $supplier->created_at->format('Y-m-d'),
                'last_activity' => $supplier->updated_at->diffForHumans()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'supplier' => $supplier,
                    'listings' => $listings,
                    'statistics' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supplier details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create or update a customer
     */
    public function createOrUpdateCustomer(Request $request, $id = null): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($id) {
                $user = User::findOrFail($id);
                $user->update($validated);
                $message = 'Customer updated successfully';
            } else {
                $user = User::create(array_merge($validated, [
                    'password' => bcrypt('temp_password_' . uniqid()),
                    'status' => 'active'
                ]));
                $message = 'Customer created successfully';
            }

            // Update or create user profile with additional info
            if ($request->has(['phone', 'company', 'address'])) {
                $user->userProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone_number' => $request->phone,
                        'company_name' => $request->company,
                        'address' => $request->address
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $user->load('userProfile')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Export customers or suppliers data
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:customers,suppliers',
                'format' => 'required|in:csv,excel,pdf'
            ]);

            // Placeholder for export functionality
            return response()->json([
                'success' => true,
                'message' => 'Export initiated',
                'data' => [
                    'download_url' => '/api/v1/admin/download-export/' . uniqid()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}