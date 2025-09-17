<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\BannerPricing;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
                // TODO: Send admin notification
            }

            // Send confirmation email to purchaser
            // TODO: Send confirmation email

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

        // TODO: Process refund if payment was completed
        // TODO: Send rejection notification

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
}