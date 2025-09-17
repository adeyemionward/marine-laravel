<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\Banner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    protected $flutterwaveSecretKey;
    protected $flutterwavePublicKey;
    protected $paystackSecretKey;
    protected $paystackPublicKey;

    public function __construct()
    {
        $this->flutterwaveSecretKey = config('services.flutterwave.secret_key');
        $this->flutterwavePublicKey = config('services.flutterwave.public_key');
        $this->paystackSecretKey = config('services.paystack.secret_key');
        $this->paystackPublicKey = config('services.paystack.public_key');
    }

    /**
     * Initialize payment with Flutterwave
     */
    public function initializeFlutterwavePayment(Order $order, array $customerData)
    {
        try {
            $txRef = 'MARINE_' . time() . '_' . $order->id;
            
            $payload = [
                'tx_ref' => $txRef,
                'amount' => $order->total_amount,
                'currency' => 'NGN',
                'redirect_url' => config('app.frontend_url') . '/payment/callback/flutterwave',
                'payment_options' => 'card,banktransfer,ussd,account',
                'customer' => [
                    'email' => $customerData['email'],
                    'name' => $customerData['name'],
                    'phonenumber' => $customerData['phone'] ?? null,
                ],
                'customizations' => [
                    'title' => 'Marine Equipment Purchase',
                    'description' => "Payment for Order #{$order->order_number}",
                    'logo' => config('app.url') . '/assets/logo.png'
                ],
                'meta' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->buyer_id
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->flutterwaveSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.flutterwave.com/v3/payments', $payload);

            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json('data');
                
                // Create payment record
                $payment = Payment::create([
                    'payment_reference' => $txRef,
                    'user_id' => $order->buyer_id,
                    'payable_type' => Order::class,
                    'payable_id' => $order->id,
                    'amount' => $order->total_amount,
                    'currency' => 'NGN',
                    'gateway' => 'flutterwave',
                    'gateway_reference' => $data['id'] ?? null,
                    'status' => 'pending',
                    'gateway_response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'payment_url' => $data['link'],
                        'payment_reference' => $txRef,
                        'payment_id' => $payment->id
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Payment initialization failed'
            ];

        } catch (\Exception $e) {
            Log::error('Flutterwave payment initialization failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Initialize payment with Paystack
     */
    public function initializePaystackPayment(Order $order, array $customerData)
    {
        try {
            $reference = 'MARINE_PS_' . time() . '_' . $order->id;
            
            $payload = [
                'reference' => $reference,
                'amount' => $order->total_amount * 100, // Paystack expects amount in kobo
                'email' => $customerData['email'],
                'currency' => 'NGN',
                'callback_url' => config('app.frontend_url') . '/payment/callback/paystack',
                'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->buyer_id,
                    'customer_name' => $customerData['name'],
                    'customer_phone' => $customerData['phone'] ?? null
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $payload);

            if ($response->successful() && $response->json('status') === true) {
                $data = $response->json('data');
                
                // Create payment record
                $payment = Payment::create([
                    'payment_reference' => $reference,
                    'user_id' => $order->buyer_id,
                    'payable_type' => Order::class,
                    'payable_id' => $order->id,
                    'amount' => $order->total_amount,
                    'currency' => 'NGN',
                    'gateway' => 'paystack',
                    'gateway_reference' => $data['reference'],
                    'status' => 'pending',
                    'gateway_response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'payment_url' => $data['authorization_url'],
                        'access_code' => $data['access_code'],
                        'payment_reference' => $reference,
                        'payment_id' => $payment->id
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Payment initialization failed'
            ];

        } catch (\Exception $e) {
            Log::error('Paystack payment initialization failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Verify Flutterwave payment
     */
    public function verifyFlutterwavePayment(string $transactionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->flutterwaveSecretKey,
            ])->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            if ($response->successful()) {
                $data = $response->json('data');
                $status = $data['status'] ?? '';
                
                if ($status === 'successful') {
                    $payment = Payment::where('gateway_reference', $transactionId)
                                   ->orWhere('payment_reference', $data['tx_ref'])
                                   ->first();
                    
                    if ($payment) {
                        $payment->markAsCompleted($data);
                        
                        // Update order status
                        if ($payment->payable instanceof Order) {
                            $payment->payable->markAsPaid($payment->payment_reference);
                        } elseif ($payment->payable instanceof Banner) {
                            app(BannerService::class)->completeBannerPayment($payment->payable, $payment);
                        }
                        
                        return [
                            'success' => true,
                            'payment' => $payment,
                            'data' => $data
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];

        } catch (\Exception $e) {
            Log::error('Flutterwave payment verification failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];
        }
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPaystackPayment(string $reference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($response->successful() && $response->json('status') === true) {
                $data = $response->json('data');
                $status = $data['status'] ?? '';
                
                if ($status === 'success') {
                    $payment = Payment::where('payment_reference', $reference)
                                   ->orWhere('gateway_reference', $reference)
                                   ->first();
                    
                    if ($payment) {
                        $payment->markAsCompleted($data);
                        
                        // Update order status
                        if ($payment->payable instanceof Order) {
                            $payment->payable->markAsPaid($payment->payment_reference);
                        } elseif ($payment->payable instanceof Banner) {
                            app(BannerService::class)->completeBannerPayment($payment->payable, $payment);
                        }
                        
                        return [
                            'success' => true,
                            'payment' => $payment,
                            'data' => $data
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];

        } catch (\Exception $e) {
            Log::error('Paystack payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];
        }
    }

    /**
     * Initialize banner payment with Flutterwave
     */
    public function initializeFlutterwaveBannerPayment(Banner $banner, array $customerData, Payment $payment)
    {
        try {
            $payload = [
                'tx_ref' => $payment->payment_reference,
                'amount' => $banner->purchase_price,
                'currency' => 'NGN',
                'redirect_url' => config('app.frontend_url') . '/payment/callback/flutterwave',
                'payment_options' => 'card,banktransfer,ussd,account',
                'customer' => [
                    'email' => $customerData['email'],
                    'name' => $customerData['name'],
                    'phonenumber' => $customerData['phone'] ?? null,
                ],
                'customizations' => [
                    'title' => 'Marine Banner Advertisement',
                    'description' => "Banner placement: {$banner->title}",
                    'logo' => config('app.url') . '/assets/logo.png'
                ],
                'meta' => [
                    'banner_id' => $banner->id,
                    'banner_type' => $banner->banner_type,
                    'payment_id' => $payment->id,
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->flutterwaveSecretKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.flutterwave.com/v3/payments', $payload);

            if ($response->successful()) {
                $data = $response->json('data');
                
                // Update payment with gateway reference
                $payment->update([
                    'gateway_reference' => $data['id'] ?? null,
                    'gateway_response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'payment_url' => $data['link'],
                    'reference' => $payment->payment_reference,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Payment initialization failed'
            ];

        } catch (\Exception $e) {
            Log::error('Flutterwave banner payment initialization failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Initialize banner payment with Paystack
     */
    public function initializePaystackBannerPayment(Banner $banner, array $customerData, Payment $payment)
    {
        try {
            $payload = [
                'email' => $customerData['email'],
                'amount' => $banner->purchase_price * 100, // Paystack expects amount in kobo
                'reference' => $payment->payment_reference,
                'callback_url' => config('app.frontend_url') . '/payment/callback/paystack',
                'metadata' => [
                    'banner_id' => $banner->id,
                    'banner_type' => $banner->banner_type,
                    'payment_id' => $payment->id,
                    'customer_name' => $customerData['name'],
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.paystack.co/transaction/initialize', $payload);

            if ($response->successful() && $response->json('status') === true) {
                $data = $response->json('data');
                
                // Update payment with gateway reference
                $payment->update([
                    'gateway_reference' => $data['reference'] ?? $payment->payment_reference,
                    'gateway_response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'payment_url' => $data['authorization_url'],
                    'reference' => $payment->payment_reference,
                    'data' => $data
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Payment initialization failed'
            ];

        } catch (\Exception $e) {
            Log::error('Paystack banner payment initialization failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Get payment gateway config for frontend
     */
    public function getGatewayConfig()
    {
        return [
            'flutterwave' => [
                'public_key' => $this->flutterwavePublicKey,
                'enabled' => !empty($this->flutterwaveSecretKey)
            ],
            'paystack' => [
                'public_key' => $this->paystackPublicKey,
                'enabled' => !empty($this->paystackSecretKey)
            ]
        ];
    }
}