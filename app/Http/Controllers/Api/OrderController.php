<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\EquipmentListing;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    /**
     * Create a new order
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'equipment_listing_id' => 'required|exists:equipment_listings,id',
            'delivery_method' => 'required|in:pickup,shipping,courier',
            'delivery_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
            'buyer_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $listing = EquipmentListing::findOrFail($request->equipment_listing_id);
            
            // Check if user is trying to buy their own listing
            if ($listing->seller_id == Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot purchase your own listing'
                ], 400);
            }

            // Check if listing is available
            if ($listing->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'This listing is not available for purchase'
                ], 400);
            }

            DB::beginTransaction();

            // Calculate costs
            $amount = $listing->price;
            $shippingCost = $this->calculateShippingCost($request->delivery_method, $listing);
            $taxAmount = $this->calculateTax($amount);
            $totalAmount = $amount + $shippingCost + $taxAmount;

            // Create order
            $order = Order::create([
                'buyer_id' => Auth::id(),
                'seller_id' => $listing->seller_id,
                'equipment_listing_id' => $listing->id,
                'status' => 'pending',
                'amount' => $amount,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => $listing->currency,
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $request->delivery_address,
                'billing_address' => $request->billing_address,
                'buyer_notes' => $request->buyer_notes,
                'payment_due_date' => now()->addDays(3), // 3 days to pay
            ]);

            // Load relationships for response
            $order->load(['buyer', 'seller', 'equipmentListing']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's orders (as buyer)
     */
    public function getUserOrders(Request $request): JsonResponse
    {
        $orders = Order::where('buyer_id', Auth::id())
            ->with(['seller', 'equipmentListing', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get user's sales (as seller)
     */
    public function getUserSales(Request $request): JsonResponse
    {
        $sales = Order::where('seller_id', Auth::id())
            ->with(['buyer', 'equipmentListing', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    /**
     * Get order details
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['buyer', 'seller', 'equipmentListing', 'payments'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Check if user has permission to view this order
        if ($order->buyer_id !== Auth::id() && $order->seller_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this order'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update order status (for sellers)
     */
    public function updateStatus(Request $request, string $orderNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled,disputed',
            'notes' => 'nullable|string|max:1000',
            'tracking_number' => 'nullable|string|max:100',
            'estimated_delivery' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::where('order_number', $orderNumber)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if user is the seller
            if ($order->seller_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this order'
                ], 403);
            }

            $updateData = ['status' => $request->status];
            
            if ($request->has('tracking_number')) {
                $updateData['tracking_number'] = $request->tracking_number;
            }
            
            if ($request->has('estimated_delivery')) {
                $updateData['estimated_delivery'] = $request->estimated_delivery;
            }
            
            if ($request->status === 'delivered') {
                $updateData['actual_delivery'] = now();
            }

            $order->updateStatus($request->status, $request->notes);
            $order->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::where('order_number', $orderNumber)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check permissions
            if ($order->buyer_id !== Auth::id() && $order->seller_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to cancel this order'
                ], 403);
            }

            // Check if order can be cancelled
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order cannot be cancelled'
                ], 400);
            }

            $order->updateStatus('cancelled', $request->reason);

            // Handle refunds if payment was made
            if ($order->payment_status === 'completed') {
                // Process refund logic here
                $this->processRefund($order);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate shipping cost based on delivery method and listing
     */
    private function calculateShippingCost(string $deliveryMethod, EquipmentListing $listing): float
    {
        return match($deliveryMethod) {
            'pickup' => 0.00,
            'shipping' => $listing->delivery_fee ?? 50.00, // Default shipping fee
            'courier' => $listing->delivery_fee ?? 75.00, // Default courier fee
            default => 0.00,
        };
    }

    /**
     * Calculate tax amount (if applicable)
     */
    private function calculateTax(float $amount): float
    {
        // For now, no tax calculation - can be implemented based on location
        return 0.00;
    }

    /**
     * Process refund for cancelled orders
     */
    private function processRefund(Order $order): void
    {
        // Get the payment for this order
        $payment = $order->payments()->where('status', 'completed')->first();
        
        if ($payment) {
            // Mark payment as refunded
            $payment->processRefund(null, 'Order cancelled');
            
            // Update order payment status
            $order->update(['payment_status' => 'refunded']);
        }
    }
}
