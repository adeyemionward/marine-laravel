<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BannerPurchaseRequest;
use App\Models\Banner;
use App\Models\Invoice;
use App\Models\FinancialTransaction;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BannerPurchaseController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Create a new banner purchase request
     */
    public function createPurchaseRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'required|string|max:20',
            'banner_position' => 'required|in:top,middle,bottom,left,right,hero',
            'banner_duration' => 'required|in:1_week,2_weeks,1_month,3_months,6_months,1_year',
            'target_pages' => 'nullable|array',
            'target_pages.*' => 'string|in:homepage,search,category,listing_detail,all',
            'banner_description' => 'required|string',
            'target_url' => 'nullable|url',
            'banner_image_url' => 'nullable|string',
            'company_logo_url' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Calculate price
            $price = BannerPurchaseRequest::calculatePrice(
                $validated['banner_position'],
                $validated['banner_duration']
            );

            // Create purchase request
            $purchaseRequest = BannerPurchaseRequest::create([
                'user_id' => Auth::id(),
                'company_name' => $validated['company_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'banner_position' => $validated['banner_position'],
                'banner_duration' => $validated['banner_duration'],
                'target_pages' => $validated['target_pages'] ?? ['all'],
                'banner_description' => $validated['banner_description'],
                'target_url' => $validated['target_url'] ?? null,
                'banner_image_url' => $validated['banner_image_url'] ?? null,
                'company_logo_url' => $validated['company_logo_url'] ?? null,
                'price' => $price,
                'status' => BannerPurchaseRequest::STATUS_PENDING,
                'payment_status' => BannerPurchaseRequest::PAYMENT_PENDING,
            ]);

            // Generate invoice
            $invoice = $this->generateInvoice($purchaseRequest);

            // Update purchase request with invoice details
            $purchaseRequest->update([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_sent_at' => now(),
                'payment_status' => BannerPurchaseRequest::PAYMENT_INVOICED,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Banner purchase request created successfully',
                'data' => [
                    'purchase_request' => $purchaseRequest->fresh()->load('invoice'),
                    'invoice' => $invoice,
                    'payment_instructions' => $this->getPaymentInstructions($invoice),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create banner purchase request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate invoice for banner purchase
     */
    protected function generateInvoice(BannerPurchaseRequest $purchaseRequest): Invoice
    {
        $invoiceData = [
            'user_id' => $purchaseRequest->user_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => $purchaseRequest->price,
            'tax_rate' => 7.5, // VAT
            'tax_amount' => $purchaseRequest->price * 0.075,
            'total' => $purchaseRequest->price * 1.075,
            'status' => 'pending',
            'invoice_type' => 'banner_purchase',
            'description' => "Banner Advertisement - {$purchaseRequest->banner_position} position for {$purchaseRequest->banner_duration}",
            'items' => json_encode([
                [
                    'description' => "Banner Advertisement - {$purchaseRequest->banner_position} position",
                    'quantity' => 1,
                    'rate' => $purchaseRequest->price,
                    'amount' => $purchaseRequest->price,
                    'duration' => $purchaseRequest->banner_duration,
                    'position' => $purchaseRequest->banner_position,
                ]
            ]),
        ];

        return Invoice::create($invoiceData);
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'BAN';
        $year = date('Y');
        $month = date('m');

        $lastInvoice = Invoice::where('invoice_number', 'like', "$prefix-$year$month-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "$prefix-$year$month-$newNumber";
    }

    /**
     * Get payment instructions
     */
    protected function getPaymentInstructions(Invoice $invoice): array
    {
        return [
            'bank_details' => [
                'bank_name' => 'First Bank of Nigeria',
                'account_name' => 'Marine Equipment Marketplace Ltd',
                'account_number' => '3123456789',
                'reference' => $invoice->invoice_number,
            ],
            'alternative_payment' => [
                'paystack' => true,
                'flutterwave' => true,
                'bank_transfer' => true,
            ],
            'instructions' => 'Please use the invoice number as payment reference. Send payment confirmation to billing@marine.africa',
        ];
    }

    /**
     * Get user's banner purchase requests
     */
    public function getUserPurchaseRequests(Request $request): JsonResponse
    {
        try {
            $requests = BannerPurchaseRequest::where('user_id', Auth::id())
                ->with(['invoice', 'banner'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $requests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch purchase requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Get all banner purchase requests
     */
    public function getAllPurchaseRequests(Request $request): JsonResponse
    {
        try {
            $query = BannerPurchaseRequest::with(['user', 'invoice', 'banner', 'confirmedBy']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->input('payment_status'));
            }

            $requests = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $requests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch purchase requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Confirm payment for a purchase request
     */
    public function confirmPayment(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_reference' => 'required|string',
            'payment_method' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $purchaseRequest = BannerPurchaseRequest::findOrFail($id);

            // Check if already confirmed
            if ($purchaseRequest->isPaymentConfirmed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment already confirmed',
                ], 400);
            }

            // Update purchase request
            $purchaseRequest->update([
                'payment_status' => BannerPurchaseRequest::PAYMENT_CONFIRMED,
                'payment_confirmed_at' => now(),
                'payment_received_at' => now(),
                'confirmed_by' => Auth::id(),
                'status' => BannerPurchaseRequest::STATUS_APPROVED,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // Update invoice status
            if ($purchaseRequest->invoice) {
                $purchaseRequest->invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_reference' => $validated['payment_reference'],
                    'payment_method' => $validated['payment_method'],
                ]);
            }

            // Record financial transaction for banner payment
            FinancialTransaction::recordBannerPayment(
                $purchaseRequest,
                $purchaseRequest->price,
                $validated['payment_method'],
                $validated['payment_reference']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully. Banner can now be created.',
                'data' => $purchaseRequest->fresh()->load(['invoice', 'user']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Create banner for confirmed purchase request
     */
    public function createBannerFromRequest(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'media_url' => 'required|string',
            'link_url' => 'required|url',
            'additional_settings' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $purchaseRequest = BannerPurchaseRequest::findOrFail($id);

            // Check if ready for banner creation
            if (!$purchaseRequest->isReadyForBannerCreation()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase request is not ready for banner creation. Payment must be confirmed first.',
                ], 400);
            }

            // Calculate banner dates
            $startDate = now();
            $endDate = now()->addDays($purchaseRequest->getDurationInDays());

            // Create banner
            $banner = Banner::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? $purchaseRequest->banner_description,
                'media_url' => $validated['media_url'],
                'link_url' => $validated['link_url'],
                'media_type' => 'image',
                'position' => $purchaseRequest->banner_position,
                'banner_type' => Banner::TYPE_SPONSORED,
                'display_context' => $this->mapTargetPagesToContext($purchaseRequest->target_pages),
                'status' => 'active',
                'is_active' => true,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'purchase_price' => $purchaseRequest->price,
                'purchaser_id' => $purchaseRequest->user_id,
                'purchase_status' => 'paid',
                'created_by' => Auth::user()->profile->id ?? Auth::id(),
                'priority' => 5,
                'show_on_desktop' => true,
                'show_on_mobile' => true,
                'banner_size' => Banner::SIZE_LARGE,
                'sort_order' => 0,
            ]);

            // Update purchase request
            $purchaseRequest->update([
                'banner_id' => $banner->id,
                'status' => BannerPurchaseRequest::STATUS_COMPLETED,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => [
                    'banner' => $banner,
                    'purchase_request' => $purchaseRequest->fresh()->load('banner'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create banner',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map target pages to display context
     */
    protected function mapTargetPagesToContext(array $targetPages): string
    {
        if (in_array('all', $targetPages)) {
            return Banner::CONTEXT_HOMEPAGE;
        }

        $contextMap = [
            'homepage' => Banner::CONTEXT_HOMEPAGE,
            'search' => Banner::CONTEXT_SEARCH,
            'category' => Banner::CONTEXT_CATEGORY,
            'listing_detail' => Banner::CONTEXT_LISTING_DETAIL,
        ];

        return $contextMap[$targetPages[0]] ?? Banner::CONTEXT_HOMEPAGE;
    }

    /**
     * Get banner pricing information
     */
    public function getPricing(): JsonResponse
    {
        $pricing = [
            'positions' => [
                'hero' => [
                    'name' => 'Hero Banner',
                    'description' => 'Premium placement at the top of pages',
                    'monthly_price' => 50000,
                ],
                'top' => [
                    'name' => 'Top Banner',
                    'description' => 'Prominent placement below hero section',
                    'monthly_price' => 30000,
                ],
                'middle' => [
                    'name' => 'Middle Banner',
                    'description' => 'Strategic placement within content',
                    'monthly_price' => 25000,
                ],
                'bottom' => [
                    'name' => 'Bottom Banner',
                    'description' => 'Footer area placement',
                    'monthly_price' => 20000,
                ],
                'left' => [
                    'name' => 'Left Sidebar',
                    'description' => 'Sidebar placement on left side',
                    'monthly_price' => 15000,
                ],
                'right' => [
                    'name' => 'Right Sidebar',
                    'description' => 'Sidebar placement on right side',
                    'monthly_price' => 15000,
                ],
            ],
            'durations' => [
                '1_week' => ['label' => '1 Week', 'discount' => '70%'],
                '2_weeks' => ['label' => '2 Weeks', 'discount' => '50%'],
                '1_month' => ['label' => '1 Month', 'discount' => '0%'],
                '3_months' => ['label' => '3 Months', 'discount' => '10%'],
                '6_months' => ['label' => '6 Months', 'discount' => '17%'],
                '1_year' => ['label' => '1 Year', 'discount' => '25%'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }
}