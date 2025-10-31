<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\BannerPricing;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\BannerApprovalRequiredNotification;
use App\Notifications\BannerPaymentConfirmedNotification;
use App\Notifications\BannerRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BannerService
{
    public function __construct(
        private PaymentGatewayService $paymentGateway
    ) {}

    public function createBannerPurchase(array $data, User $user): Banner
    {
        return DB::transaction(function () use ($data, $user) {
            // Get pricing information
            $pricing = BannerPricing::active()
                ->forType($data['banner_type'])
                ->first();

            if (!$pricing) {
                throw new \Exception('Pricing not found for banner type: ' . $data['banner_type']);
            }

            // Calculate price
            $duration = $data['duration'] ?? $pricing->duration_value;
            $isPremium = $data['is_premium'] ?? false;
            $totalPrice = $pricing->calculatePrice($duration, $isPremium);

            // Calculate dates
            $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date']) : now();
            $endDate = $startDate->copy()->addDays($pricing->getDurationInDays() * $duration);

            // Check for conflicts
            $this->checkBannerConflicts($data['banner_type'], $startDate, $endDate, $pricing->max_concurrent);

            // Create banner
            $banner = Banner::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'media_type' => $data['media_type'],
                'media_url' => $data['media_url'],
                'link_url' => $data['link_url'] ?? null,
                'banner_type' => $data['banner_type'],
                'position' => $data['position'] ?? 'top',
                'purchaser_id' => $user->id,
                'created_by' => $user->userProfile?->id ?? $user->id,
                'purchase_price' => $totalPrice,
                'purchase_status' => 'pending_payment',
                'duration_days' => $pricing->getDurationInDays() * $duration,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'pending', // Will be activated after payment
                'auto_approve' => $data['auto_approve'] ?? false,
                'pricing_details' => [
                    'base_price' => $pricing->base_price,
                    'duration' => $duration,
                    'is_premium' => $isPremium,
                    'premium_multiplier' => $isPremium ? $pricing->premium_multiplier : 1,
                    'total_price' => $totalPrice,
                    'pricing_id' => $pricing->id,
                ],
                'priority' => $this->calculatePriority($data['banner_type'], $isPremium),
            ]);

            return $banner;
        });
    }

    public function initializeBannerPayment(Banner $banner, array $customerData, string $gateway = 'paystack')
    {
        // Create payment record
        $payment = Payment::create([
            'payment_reference' => 'BANNER_' . time() . '_' . $banner->id,
            'user_id' => $banner->purchaser_id,
            'payable_type' => Banner::class,
            'payable_id' => $banner->id,
            'amount' => $banner->purchase_price,
            'status' => 'pending',
            'gateway' => $gateway,
            'metadata' => [
                'banner_id' => $banner->id,
                'banner_type' => $banner->banner_type,
                'customer_data' => $customerData,
            ],
        ]);

        // Update banner with payment reference
        $banner->update(['payment_reference' => $payment->payment_reference]);

        // Initialize payment with gateway
        if ($gateway === 'flutterwave') {
            return $this->paymentGateway->initializeFlutterwaveBannerPayment($banner, $customerData, $payment);
        } else {
            return $this->paymentGateway->initializePaystackBannerPayment($banner, $customerData, $payment);
        }
    }

    public function completeBannerPayment(Banner $banner, Payment $payment): bool
    {
        return DB::transaction(function () use ($banner, $payment) {
            // Mark payment as completed
            $payment->markAsCompleted();

            // Mark banner as paid
            $banner->markAsPaid();

            // Activate banner if auto-approve is enabled
            if ($banner->auto_approve) {
                $banner->update([
                    'status' => 'active',
                    'start_date' => now(),
                ]);
            }

            // Send notification to admin for approval if needed
            if (!$banner->auto_approve) {
                $this->sendAdminApprovalNotification($banner);
            }

            // Send confirmation email to purchaser
            $this->sendPaymentConfirmationNotification($banner);

            return true;
        });
    }

    public function approveBanner(Banner $banner, User $admin): bool
    {
        if (!$banner->isPaid()) {
            throw new \Exception('Banner must be paid before approval');
        }

        $banner->update([
            'status' => 'active',
            'start_date' => now(),
            'admin_notes' => "Approved by {$admin->name} on " . now()->format('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function rejectBanner(Banner $banner, User $admin, string $reason): bool
    {
        $banner->update([
            'status' => 'rejected',
            'admin_notes' => "Rejected by {$admin->name}: {$reason}",
        ]);

        // Process refund if payment was completed
        if ($banner->isPaid()) {
            $this->processRefund($banner);
        }

        // Send rejection notification
        $this->sendRejectionNotification($banner, $reason);

        return true;
    }

    public function getActiveBanners(string $type = null, string $position = null)
    {
        $query = Banner::active()->paid();

        if ($type) {
            $query->forBannerType($type);
        }

        if ($position) {
            $query->position($position);
        }

        return $query->ordered()->get();
    }

    public function getBannerPricing()
    {
        return BannerPricing::active()
            ->get()
            ->groupBy('banner_type');
    }

    private function checkBannerConflicts(string $bannerType, Carbon $startDate, Carbon $endDate, int $maxConcurrent): void
    {
        $conflictingBanners = Banner::forBannerType($bannerType)
            ->where('status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->count();

        if ($conflictingBanners >= $maxConcurrent) {
            throw new \Exception("Maximum concurrent banners ({$maxConcurrent}) exceeded for {$bannerType} during selected period");
        }
    }

    private function calculatePriority(string $bannerType, bool $isPremium): int
    {
        $basePriority = match ($bannerType) {
            'header' => 100,
            'hero' => 90,
            'sidebar' => 80,
            'footer' => 70,
            default => 50,
        };

        return $isPremium ? $basePriority + 10 : $basePriority;
    }

    /**
     * Send admin notification for banner approval
     */
    private function sendAdminApprovalNotification(Banner $banner): void
    {
        try {
            // Get all admin users
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            // Notify all admins
            Notification::send($admins, new BannerApprovalRequiredNotification($banner));

        } catch (\Exception $e) {
            \Log::error('Failed to send admin approval notification', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment confirmation notification to purchaser
     */
    private function sendPaymentConfirmationNotification(Banner $banner): void
    {
        try {
            $purchaser = $banner->purchaser;
            $purchaser->notify(new BannerPaymentConfirmedNotification($banner));

        } catch (\Exception $e) {
            \Log::error('Failed to send payment confirmation notification', [
                'banner_id' => $banner->id,
                'purchaser_id' => $banner->purchaser_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send rejection notification to purchaser
     */
    private function sendRejectionNotification(Banner $banner, string $reason): void
    {
        try {
            $purchaser = $banner->purchaser;
            $purchaser->notify(new BannerRejectedNotification($banner, $reason));

        } catch (\Exception $e) {
            \Log::error('Failed to send rejection notification', [
                'banner_id' => $banner->id,
                'purchaser_id' => $banner->purchaser_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process refund for rejected banner
     */
    private function processRefund(Banner $banner): void
    {
        try {
            // Find the payment
            $payment = Payment::where('payable_type', Banner::class)
                ->where('payable_id', $banner->id)
                ->where('status', 'completed')
                ->first();

            if (!$payment) {
                \Log::warning('No payment found for banner refund', ['banner_id' => $banner->id]);
                return;
            }

            // Process refund through payment gateway
            $refundResult = $this->paymentGateway->processRefund($payment, $banner->purchase_price, 'Banner rejected by admin');

            if ($refundResult['success']) {
                // Update payment status
                $payment->update([
                    'status' => 'refunded',
                    'refund_reference' => $refundResult['refund_reference'],
                    'refunded_at' => now()
                ]);

                // Update banner
                $banner->update([
                    'purchase_status' => 'refunded',
                    'refund_reference' => $refundResult['refund_reference']
                ]);

                \Log::info('Banner refund processed successfully', [
                    'banner_id' => $banner->id,
                    'payment_id' => $payment->id,
                    'refund_reference' => $refundResult['refund_reference']
                ]);
            } else {
                \Log::error('Failed to process banner refund', [
                    'banner_id' => $banner->id,
                    'payment_id' => $payment->id,
                    'error' => $refundResult['error'] ?? 'Unknown error'
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Exception while processing banner refund', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}