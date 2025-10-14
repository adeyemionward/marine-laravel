<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Invoice;
use App\Models\EquipmentListing;
use App\Models\CreditLimitRequest;
use App\Models\CreditLimitHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
                      ->selectRaw('COALESCE(SUM(total_amount), 0)');
                }, 'total_spent')
                ->selectSub(function($q) {
                    $q->from('invoices')
                      ->whereColumn('invoices.user_id', 'users.id')
                      ->where('status', 'paid')
                      ->selectRaw('COUNT(*)');
                }, 'purchase_count')
                ->selectSub(function($q) {
                    $q->from('invoices')
                      ->whereColumn('invoices.user_id', 'users.id')
                      ->whereIn('status', ['pending', 'overdue'])
                      ->selectRaw('COALESCE(SUM(total_amount), 0)');
                }, 'current_balance')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            // Add credit_limit and other fields from user_profiles
            $customers->getCollection()->transform(function ($customer) {
                if ($customer->profile) {
                    $customer->credit_limit = $customer->profile->credit_limit ?? 0;
                    $customer->customer_type = $customer->profile->customer_type ?? 'individual';
                    $customer->customer_code = $customer->profile->customer_code ?? 'C-' . str_pad($customer->id, 6, '0', STR_PAD_LEFT);
                    $customer->tax_id = $customer->profile->tax_id;
                    $customer->business_registration = $customer->profile->business_registration;
                    $customer->city = $customer->profile->city;
                    $customer->state = $customer->profile->state;
                    $customer->postal_code = $customer->profile->postal_code;
                    $customer->country = $customer->profile->country ?? 'Nigeria';
                    $customer->status = $customer->profile->status ?? 'active';
                } else {
                    $customer->credit_limit = 0;
                    $customer->customer_type = 'individual';
                    $customer->customer_code = 'C-' . str_pad($customer->id, 6, '0', STR_PAD_LEFT);
                    $customer->tax_id = null;
                    $customer->business_registration = null;
                    $customer->city = null;
                    $customer->state = null;
                    $customer->postal_code = null;
                    $customer->country = 'Nigeria';
                    $customer->status = 'active';
                }

                // Ensure current_balance is set
                $customer->current_balance = $customer->current_balance ?? 0;

                return $customer;
            });

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

            // Add supplier-specific fields from user_profiles
            $suppliers->getCollection()->transform(function ($supplier) {
                if ($supplier->profile) {
                    $supplier->supplier_type = $supplier->profile->supplier_type ?? 'equipment_supplier';
                    $supplier->supplier_code = $supplier->profile->supplier_code ?? 'S-' . str_pad($supplier->id, 6, '0', STR_PAD_LEFT);
                    $supplier->payment_terms = $supplier->profile->payment_terms ?? 30;
                    $supplier->is_preferred = $supplier->profile->is_preferred ?? false;
                    $supplier->city = $supplier->profile->city;
                    $supplier->state = $supplier->profile->state;
                    $supplier->postal_code = $supplier->profile->postal_code;
                    $supplier->country = $supplier->profile->country ?? 'Nigeria';
                    $supplier->status = $supplier->profile->status ?? 'active';
                } else {
                    $supplier->supplier_type = 'equipment_supplier';
                    $supplier->supplier_code = 'S-' . str_pad($supplier->id, 6, '0', STR_PAD_LEFT);
                    $supplier->payment_terms = 30;
                    $supplier->is_preferred = false;
                    $supplier->city = null;
                    $supplier->state = null;
                    $supplier->postal_code = null;
                    $supplier->country = 'Nigeria';
                    $supplier->status = 'active';
                }

                return $supplier;
            });

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
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'customer_type' => 'nullable|in:individual,business,government,nonprofit',
                'credit_limit' => 'nullable|numeric|min:0',
                'tax_id' => 'nullable|string|max:100',
                'business_registration' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|in:active,inactive,pending,suspended'
            ]);

            if ($id) {
                $user = User::findOrFail($id);
                $user->update(['name' => $validated['name'], 'email' => $validated['email']]);
                $message = 'Customer updated successfully';
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => bcrypt('temp_password_' . uniqid())
                ]);
                $message = 'Customer created successfully';
            }

            // Update or create user profile with customer-specific info
            $profileData = [
                'phone_number' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'Nigeria',
                'customer_type' => $request->customer_type ?? 'individual',
                'credit_limit' => $request->credit_limit ?? 0,
                'tax_id' => $request->tax_id,
                'business_registration' => $request->business_registration,
                'notes' => $request->notes,
                'status' => $request->status ?? 'active'
            ];

            // Generate customer code if creating new customer
            if (!$id) {
                $profileData['customer_code'] = 'C-' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            }

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                array_filter($profileData, function($value) {
                    return $value !== null;
                })
            );

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
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'supplier_type' => 'nullable|in:equipment_supplier,parts_supplier,service_provider,maintenance_provider,consultant,other',
                'payment_terms' => 'nullable|integer|min:0',
                'is_preferred' => 'nullable|boolean',
                'tax_id' => 'nullable|string|max:100',
                'business_registration' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:1000',
                'status' => 'nullable|in:active,inactive,pending,suspended'
            ]);

            if ($id) {
                $user = User::findOrFail($id);
                $user->update(['name' => $validated['name'], 'email' => $validated['email']]);
                $message = 'Supplier updated successfully';
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => bcrypt('temp_password_' . uniqid())
                ]);

                // Assign seller role
                $sellerRole = \App\Models\Role::where('name', 'seller')->first();
                if ($sellerRole) {
                    $user->role_id = $sellerRole->id;
                    $user->save();
                }

                $message = 'Supplier created successfully';
            }

            // Update or create user profile with supplier-specific info
            $profileData = [
                'phone_number' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country ?? 'Nigeria',
                'supplier_type' => $request->supplier_type ?? 'equipment_supplier',
                'payment_terms' => $request->payment_terms ?? 30,
                'is_preferred' => $request->boolean('is_preferred', false),
                'tax_id' => $request->tax_id,
                'business_registration' => $request->business_registration,
                'notes' => $request->notes,
                'status' => $request->status ?? 'active'
            ];

            // Generate supplier code if creating new supplier
            if (!$id) {
                $profileData['supplier_code'] = 'S-' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            }

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                array_filter($profileData, function($value) {
                    return $value !== null;
                })
            );

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $user->load('profile')
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

    /**
     * Request credit limit change
     */
    public function requestCreditLimitChange(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'requested_limit' => 'required|numeric|min:0',
                'reason' => 'nullable|string|max:1000'
            ]);

            $user = User::with('profile')->findOrFail($validated['user_id']);
            $currentLimit = $user->profile->credit_limit ?? 0;

            $creditRequest = CreditLimitRequest::create([
                'user_id' => $validated['user_id'],
                'current_limit' => $currentLimit,
                'requested_limit' => $validated['requested_limit'],
                'reason' => $validated['reason'],
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Credit limit change request submitted successfully',
                'data' => $creditRequest->load('user')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit credit limit request',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all credit limit requests
     */
    public function getCreditLimitRequests(Request $request): JsonResponse
    {
        try {
            $query = CreditLimitRequest::with(['user.profile', 'reviewer']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $requests = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch credit limit requests',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Approve or reject credit limit request
     */
    public function reviewCreditLimitRequest(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:approve,reject',
                'review_notes' => 'nullable|string|max:1000'
            ]);

            $creditRequest = CreditLimitRequest::findOrFail($id);

            if ($creditRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been reviewed'
                ], 400);
            }

            DB::beginTransaction();

            $creditRequest->update([
                'status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => Auth::id(),
                'review_notes' => $validated['review_notes'],
                'reviewed_at' => now()
            ]);

            // If approved, update the customer's credit limit
            if ($validated['action'] === 'approve') {
                $user = User::findOrFail($creditRequest->user_id);
                $oldLimit = $user->profile->credit_limit ?? 0;

                $user->profile()->update([
                    'credit_limit' => $creditRequest->requested_limit
                ]);

                // Record in history
                CreditLimitHistory::create([
                    'user_id' => $user->id,
                    'old_limit' => $oldLimit,
                    'new_limit' => $creditRequest->requested_limit,
                    'change_type' => 'request_approved',
                    'changed_by' => Auth::id(),
                    'reason' => $creditRequest->reason
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Credit limit request ' . ($validated['action'] === 'approve' ? 'approved' : 'rejected') . ' successfully',
                'data' => $creditRequest->load(['user.profile', 'reviewer'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to review credit limit request',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get credit limit history for a customer
     */
    public function getCreditLimitHistory($userId): JsonResponse
    {
        try {
            $history = CreditLimitHistory::where('user_id', $userId)
                ->with(['admin'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch credit limit history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Automatically adjust credit limit based on payment history
     */
    public function autoAdjustCreditLimit($userId): JsonResponse
    {
        try {
            $user = User::with('profile')->findOrFail($userId);

            // Calculate payment statistics
            $stats = [
                'total_paid' => Invoice::where('user_id', $userId)
                    ->where('status', 'paid')
                    ->sum('total_amount'),
                'total_transactions' => Invoice::where('user_id', $userId)
                    ->where('status', 'paid')
                    ->count(),
                'on_time_payments' => Invoice::where('user_id', $userId)
                    ->where('status', 'paid')
                    ->whereRaw('paid_at <= due_date')
                    ->count(),
                'late_payments' => Invoice::where('user_id', $userId)
                    ->where('status', 'paid')
                    ->whereRaw('paid_at > due_date')
                    ->count(),
                'overdue_count' => Invoice::where('user_id', $userId)
                    ->where('status', 'overdue')
                    ->count(),
            ];

            $currentLimit = $user->profile->credit_limit ?? 0;
            $newLimit = $currentLimit;

            // Calculate payment reliability score (0-100)
            if ($stats['total_transactions'] > 0) {
                $onTimeRate = ($stats['on_time_payments'] / $stats['total_transactions']) * 100;

                // Increase credit limit if good payment history
                if ($onTimeRate >= 90 && $stats['total_transactions'] >= 5 && $stats['overdue_count'] == 0) {
                    $newLimit = $currentLimit * 1.25; // Increase by 25%
                }
                // Decrease if poor payment history
                elseif ($onTimeRate < 50 || $stats['overdue_count'] > 3) {
                    $newLimit = $currentLimit * 0.75; // Decrease by 25%
                }
            }

            // Only update if there's a significant change (more than 5%)
            if (abs($newLimit - $currentLimit) / $currentLimit > 0.05) {
                DB::beginTransaction();

                $user->profile()->update([
                    'credit_limit' => $newLimit
                ]);

                CreditLimitHistory::create([
                    'user_id' => $user->id,
                    'old_limit' => $currentLimit,
                    'new_limit' => $newLimit,
                    'change_type' => 'automatic',
                    'changed_by' => null,
                    'reason' => 'Automatic adjustment based on payment history: ' .
                                round($onTimeRate, 2) . '% on-time payment rate'
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Credit limit adjusted automatically',
                    'data' => [
                        'old_limit' => $currentLimit,
                        'new_limit' => $newLimit,
                        'stats' => $stats
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No significant change needed',
                    'data' => [
                        'current_limit' => $currentLimit,
                        'stats' => $stats
                    ]
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-adjust credit limit',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}