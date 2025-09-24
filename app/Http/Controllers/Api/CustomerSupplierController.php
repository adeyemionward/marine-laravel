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
            $query = User::with(['profile', 'role'])
                ->withCount(['listings', 'invoices']);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->status === 'inactive') {
                    $query->whereNull('email_verified_at');
                }
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
            $query = User::with(['profile', 'role', 'sellerProfile'])
                ->whereHas('role', function($q) {
                    $q->where('name', 'seller');
                })
                ->orWhereHas('listings');

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
                if ($request->status === 'active') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($request->status === 'inactive') {
                    $query->whereNull('email_verified_at');
                }
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
                      ->whereColumn('equipment_listings.seller_id', 'users.id')
                      ->where('status', 'active')
                      ->selectRaw('COALESCE(COUNT(*), 0)');
                }, 'active_listings')
                ->selectSub(function($q) {
                    $q->from('equipment_listings')
                      ->whereColumn('equipment_listings.seller_id', 'users.id')
                      ->where('status', 'sold')
                      ->selectRaw('COALESCE(COUNT(*), 0)');
                }, 'sold_listings')
                ->selectSub(function($q) {
                    $q->from('invoices')
                      ->whereColumn('invoices.user_id', 'users.id')
                      ->where('invoices.status', 'paid')
                      ->selectRaw('COALESCE(SUM(total_amount), 0)');
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
            $customer = User::with(['profile', 'role', 'listings', 'invoices'])
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
                'active_listings' => EquipmentListing::where('seller_id', $id)
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
            $supplier = User::with(['profile', 'role', 'sellerProfile', 'listings'])
                ->findOrFail($id);

            // Get listing history
            $listings = EquipmentListing::where('seller_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get statistics
            $stats = [
                'total_listings' => EquipmentListing::where('seller_id', $id)->count(),
                'active_listings' => EquipmentListing::where('seller_id', $id)
                    ->where('status', 'active')
                    ->count(),
                'sold_listings' => EquipmentListing::where('seller_id', $id)
                    ->where('status', 'sold')
                    ->count(),
                'total_revenue' => Invoice::join('seller_applications', 'invoices.seller_application_id', '=', 'seller_applications.id')
                    ->where('seller_applications.seller_id', $id)
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
                $user->profile()->updateOrCreate(
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
                'data' => $user->load('profile')
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
     * Get customer statistics
     */
    public function getCustomerStats(): JsonResponse
    {
        try {
            $stats = [
                'total_customers' => User::count(),
                'business_customers' => User::whereHas('profile', function($q) {
                        $q->whereNotNull('company_name');
                    })->count(),
                'individual_customers' => User::whereDoesntHave('profile', function($q) {
                        $q->whereNotNull('company_name');
                    })->count(),
                'verified_customers' => User::whereHas('profile', function($q) {
                        $q->where('is_verified', true);
                    })->distinct()->count(),
                'active_customers' => User::whereHas('invoices', function($q) {
                        $q->where('created_at', '>=', Carbon::now()->subDays(30));
                    })->distinct()->count(),
                'total_revenue' => Invoice::where('status', 'paid')->sum('total_amount'),
                'avg_order_value' => Invoice::where('status', 'paid')->avg('total_amount')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer stats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customer statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get supplier statistics
     */
    public function getSupplierStats(): JsonResponse
    {
        try {
            $stats = [
                'total_suppliers' => User::whereHas('role', function($q) {
                        $q->where('name', 'seller');
                    })->orWhereHas('listings')->distinct()->count(),
                'preferred_suppliers' => User::whereHas('sellerProfile', function($q) {
                        $q->whereNotNull('verified_at');
                    })->distinct()->count(),
                'equipment_suppliers' => User::whereHas('listings', function($q) {
                        $q->whereHas('category', function($cat) {
                            $cat->where('name', 'like', '%equipment%');
                        });
                    })->distinct()->count(),
                'service_providers' => User::whereHas('listings', function($q) {
                        $q->whereHas('category', function($cat) {
                            $cat->where('name', 'like', '%service%');
                        });
                    })->distinct()->count(),
                'active_suppliers' => User::whereHas('listings', function($q) {
                        $q->where('status', 'active')
                          ->where('created_at', '>=', Carbon::now()->subDays(30));
                    })->distinct()->count(),
                'total_listings' => EquipmentListing::count(),
                'active_listings' => EquipmentListing::where('status', 'active')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Supplier stats error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supplier statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create or update a supplier
     */
    public function createOrUpdateSupplier(Request $request, $id = null): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'supplier_type' => 'nullable|string|max:100',
                'payment_terms' => 'nullable|integer|min:0',
                'is_preferred' => 'nullable|boolean',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($id) {
                $user = User::findOrFail($id);
                $user->update($validated);
                $message = 'Supplier updated successfully';
            } else {
                $user = User::create(array_merge($validated, [
                    'password' => bcrypt('temp_password_' . uniqid()),
                    'status' => 'active'
                ]));

                // Assign seller role
                $sellerRole = \App\Models\Role::where('name', 'seller')->first();
                if ($sellerRole) {
                    $user->role_id = $sellerRole->id;
                    $user->save();
                }

                $message = 'Supplier created successfully';
            }

            // Update or create seller profile
            if ($request->has(['company', 'supplier_type', 'payment_terms', 'is_preferred'])) {
                $user->sellerProfile()->updateOrCreate(
                    ['seller_id' => $user->id],
                    [
                        'business_name' => $request->company,
                        'supplier_type' => $request->supplier_type,
                        'payment_terms' => $request->payment_terms,
                        'is_preferred' => $request->boolean('is_preferred'),
                        'notes' => $request->notes
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $user->load('sellerProfile')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save supplier',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a customer
     */
    public function deleteCustomer($id): JsonResponse
    {
        try {
            $customer = User::findOrFail($id);

            // Check if customer has any invoices
            if ($customer->invoices()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with existing transactions'
                ], 400);
            }

            $customer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a supplier
     */
    public function deleteSupplier($id): JsonResponse
    {
        try {
            $supplier = User::findOrFail($id);

            // Check if supplier has any active listings
            if ($supplier->listings()->where('status', 'active')->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete supplier with active listings'
                ], 400);
            }

            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier',
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