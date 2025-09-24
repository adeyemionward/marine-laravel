<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerPurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'contact_email',
        'contact_phone',
        'banner_position',
        'banner_duration',
        'target_pages',
        'banner_description',
        'target_url',
        'price',
        'payment_status',
        'invoice_id',
        'invoice_number',
        'invoice_sent_at',
        'payment_received_at',
        'payment_confirmed_at',
        'confirmed_by',
        'banner_id',
        'status',
        'admin_notes',
        'rejection_reason',
        'banner_image_url',
        'company_logo_url',
    ];

    protected $casts = [
        'target_pages' => 'array',
        'invoice_sent_at' => 'datetime',
        'payment_received_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    // Payment status constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_INVOICED = 'invoiced';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_CONFIRMED = 'confirmed';
    const PAYMENT_CANCELLED = 'cancelled';

    // Banner duration constants
    const DURATION_1_WEEK = '1_week';
    const DURATION_2_WEEKS = '2_weeks';
    const DURATION_1_MONTH = '1_month';
    const DURATION_3_MONTHS = '3_months';
    const DURATION_6_MONTHS = '6_months';
    const DURATION_1_YEAR = '1_year';

    /**
     * Get the user that owns the request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who confirmed the payment
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get the associated invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the created banner
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    /**
     * Get duration in days
     */
    public function getDurationInDays(): int
    {
        return match($this->banner_duration) {
            self::DURATION_1_WEEK => 7,
            self::DURATION_2_WEEKS => 14,
            self::DURATION_1_MONTH => 30,
            self::DURATION_3_MONTHS => 90,
            self::DURATION_6_MONTHS => 180,
            self::DURATION_1_YEAR => 365,
            default => 7,
        };
    }

    /**
     * Calculate price based on position and duration
     */
    public static function calculatePrice(string $position, string $duration): float
    {
        // Base prices per position (monthly)
        $basePrices = [
            'hero' => 50000,
            'top' => 30000,
            'middle' => 25000,
            'bottom' => 20000,
            'left' => 15000,
            'right' => 15000,
        ];

        // Duration multipliers
        $durationMultipliers = [
            self::DURATION_1_WEEK => 0.3,
            self::DURATION_2_WEEKS => 0.5,
            self::DURATION_1_MONTH => 1,
            self::DURATION_3_MONTHS => 2.7,
            self::DURATION_6_MONTHS => 5,
            self::DURATION_1_YEAR => 9,
        ];

        $basePrice = $basePrices[$position] ?? 20000;
        $multiplier = $durationMultipliers[$duration] ?? 1;

        return round($basePrice * $multiplier, 2);
    }

    /**
     * Check if payment is confirmed
     */
    public function isPaymentConfirmed(): bool
    {
        return $this->payment_status === self::PAYMENT_CONFIRMED;
    }

    /**
     * Check if ready for banner creation
     */
    public function isReadyForBannerCreation(): bool
    {
        return $this->isPaymentConfirmed() &&
               $this->status === self::STATUS_APPROVED &&
               !$this->banner_id;
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAwaitingPayment($query)
    {
        return $query->whereIn('payment_status', [self::PAYMENT_INVOICED, self::PAYMENT_PAID]);
    }

    public function scopeReadyForBannerCreation($query)
    {
        return $query->where('payment_status', self::PAYMENT_CONFIRMED)
                     ->where('status', self::STATUS_APPROVED)
                     ->whereNull('banner_id');
    }
}