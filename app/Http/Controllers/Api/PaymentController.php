<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentGateway;

    public function __construct(PaymentGatewayService $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * Initialize payment for an order
     */
    public function initializePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'gateway' => 'required|in:flutterwave,paystack',
            'customer_data' => 'required|array',
            'customer_data.name' => 'required|string',
            'customer_data.email' => 'required|email',
            'customer_data.phone' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::find($request->order_id);
            
            // Verify user owns this order
            if ($order->buyer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to order'
                ], 403);
            }

            // Check if order is already paid
            if ($order->payment_status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order has already been paid'
                ], 400);
            }

            $customerData = $request->customer_data;
            $gateway = $request->gateway;

            if ($gateway === 'flutterwave') {
                $result = $this->paymentGateway->initializeFlutterwavePayment($order, $customerData);
            } else {
                $result = $this->paymentGateway->initializePaystackPayment($order, $customerData);
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment initialized successfully',
                    'data' => $result['data']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment initialization failed'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment initialization error', [
                'user_id' => Auth::id(),
                'order_id' => $request->order_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed'
            ], 500);
        }
    }

    /**
     * Handle Flutterwave webhook
     */
    public function flutterwaveWebhook(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('verif-hash');
            $secretHash = config('services.flutterwave.secret_hash');

            if (!$signature || $signature !== $secretHash) {
                return response()->json(['status' => 'error'], 401);
            }

            $payload = $request->all();
            $eventType = $payload['event'] ?? '';

            if (in_array($eventType, ['charge.completed', 'transfer.completed'])) {
                $data = $payload['data'];
                $txRef = $data['tx_ref'];
                $transactionId = $data['id'];
                $status = $data['status'];

                if ($status === 'successful') {
                    // Find payment by transaction reference
                    $payment = Payment::where('gateway_reference', $txRef)
                        ->orWhere('payment_reference', $txRef)
                        ->where('status', 'pending')
                        ->first();

                    if ($payment) {
                        // Verify the transaction with Flutterwave
                        $result = $this->paymentGateway->verifyFlutterwavePayment($transactionId);

                        if ($result['success'] && $result['amount'] == $payment->amount) {
                            // Mark payment as completed
                            $payment->update([
                                'gateway_reference' => $transactionId,
                                'gateway_response' => $data,
                            ]);

                            $payment->markAsCompleted($data);

                            Log::info('Flutterwave payment completed successfully', [
                                'payment_id' => $payment->id,
                                'tx_ref' => $txRef,
                                'transaction_id' => $transactionId,
                                'amount' => $payment->amount
                            ]);
                        } else {
                            Log::warning('Flutterwave payment verification failed', [
                                'tx_ref' => $txRef,
                                'transaction_id' => $transactionId,
                                'verification_result' => $result
                            ]);
                        }
                    } else {
                        Log::warning('Flutterwave payment not found', [
                            'tx_ref' => $txRef,
                            'transaction_id' => $transactionId
                        ]);
                    }
                } else if ($status === 'failed') {
                    // Handle failed payment
                    $payment = Payment::where('gateway_reference', $txRef)
                        ->orWhere('payment_reference', $txRef)
                        ->where('status', 'pending')
                        ->first();

                    if ($payment) {
                        $payment->update([
                            'status' => 'failed',
                            'gateway_reference' => $transactionId,
                            'gateway_response' => $data,
                            'failed_at' => now(),
                        ]);

                        Log::info('Flutterwave payment failed', [
                            'payment_id' => $payment->id,
                            'tx_ref' => $txRef,
                            'transaction_id' => $transactionId,
                            'reason' => $data['processor_response'] ?? 'Unknown reason'
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Flutterwave webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Handle Paystack webhook
     */
    public function paystackWebhook(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('x-paystack-signature');
            $secretKey = config('services.paystack.secret_key');
            $hash = hash_hmac('sha512', $request->getContent(), $secretKey);

            if (!$signature || $signature !== $hash) {
                return response()->json(['status' => 'error'], 401);
            }

            $payload = $request->all();
            $event = $payload['event'] ?? '';

            if (in_array($event, ['charge.success', 'charge.failed'])) {
                $data = $payload['data'];
                $reference = $data['reference'];
                $status = $data['status'];
                $amount = $data['amount']; // Amount in kobo

                if ($status === 'success') {
                    // Find payment by transaction reference
                    $payment = Payment::where('gateway_reference', $reference)
                        ->orWhere('payment_reference', $reference)
                        ->where('status', 'pending')
                        ->first();

                    if ($payment) {
                        // Verify the transaction with Paystack
                        $result = $this->paymentGateway->verifyPaystackPayment($reference);

                        // Convert kobo to naira for comparison
                        $amountInNaira = $amount / 100;

                        if ($result['success'] && $amountInNaira == $payment->amount) {
                            // Mark payment as completed
                            $payment->update([
                                'gateway_reference' => $reference,
                                'gateway_response' => $data,
                            ]);

                            $payment->markAsCompleted($data);

                            Log::info('Paystack payment completed successfully', [
                                'payment_id' => $payment->id,
                                'reference' => $reference,
                                'amount' => $payment->amount
                            ]);
                        } else {
                            Log::warning('Paystack payment verification failed', [
                                'reference' => $reference,
                                'verification_result' => $result,
                                'amount_webhook' => $amountInNaira,
                                'amount_expected' => $payment->amount
                            ]);
                        }
                    } else {
                        Log::warning('Paystack payment not found', [
                            'reference' => $reference
                        ]);
                    }
                } else if ($status === 'failed') {
                    // Handle failed payment
                    $payment = Payment::where('gateway_reference', $reference)
                        ->orWhere('payment_reference', $reference)
                        ->where('status', 'pending')
                        ->first();

                    if ($payment) {
                        $payment->update([
                            'status' => 'failed',
                            'gateway_reference' => $reference,
                            'gateway_response' => $data,
                            'failed_at' => now(),
                        ]);

                        Log::info('Paystack payment failed', [
                            'payment_id' => $payment->id,
                            'reference' => $reference,
                            'reason' => $data['gateway_response'] ?? 'Unknown reason'
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Paystack webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Verify payment manually
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'gateway' => 'required|in:flutterwave,paystack'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reference = $request->reference;
            $gateway = $request->gateway;

            if ($gateway === 'flutterwave') {
                $result = $this->paymentGateway->verifyFlutterwavePayment($reference);
            } else {
                $result = $this->paymentGateway->verifyPaystackPayment($reference);
            }

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => [
                        'payment' => $result['payment'],
                        'gateway_data' => $result['data']
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment verification failed'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'user_id' => Auth::id(),
                'reference' => $request->reference,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed'
            ], 500);
        }
    }

    /**
     * Get payment gateway configuration
     */
    public function getGatewayConfig(): JsonResponse
    {
        try {
            $config = $this->paymentGateway->getGatewayConfig();

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get gateway config', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment configuration'
            ], 500);
        }
    }

    /**
     * Get user's payment history
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $payments = Payment::where('user_id', Auth::id())
                ->with(['payable'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get payment history', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history'
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails(string $paymentReference): JsonResponse
    {
        try {
            $payment = Payment::where('payment_reference', $paymentReference)
                ->where('user_id', Auth::id())
                ->with(['payable'])
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get payment details', [
                'user_id' => Auth::id(),
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment details'
            ], 500);
        }
    }
}