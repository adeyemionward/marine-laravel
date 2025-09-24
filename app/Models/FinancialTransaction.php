<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'transaction_reference',
        'transaction_type',
        'category',
        'amount',
        'currency',
        'description',
        'notes',
        'transaction_date',
        'user_id',
        'subscription_id',
        'equipment_listing_id',
        'banner_purchase_id',
        'related_model_type',
        'related_model_id',
        'payment_method',
        'payment_reference',
        'payment_status',
        'metadata',
        'is_reconciled',
        'reconciled_at',
        'recorded_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'metadata' => 'array',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    // Boot method to generate transaction reference
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_reference)) {
                $transaction->transaction_reference = 'TXN-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function equipmentListing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class);
    }

    public function bannerPurchase(): BelongsTo
    {
        return $this->belongsTo(BannerPurchaseRequest::class, 'banner_purchase_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function relatedModel(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'expense');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    // Helper methods
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    public function getCategoryDisplayAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->category));
    }

    public function isIncome(): bool
    {
        return $this->transaction_type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->transaction_type === 'expense';
    }

    public function markAsReconciled(): void
    {
        $this->update([
            'is_reconciled' => true,
            'reconciled_at' => now()
        ]);
    }

    // Static methods for recording transactions
    public static function recordSubscriptionPayment($subscription, $amount, $paymentMethod = null, $paymentReference = null)
    {
        return self::create([
            'transaction_type' => 'income',
            'category' => 'subscription_revenue',
            'amount' => $amount,
            'description' => "Subscription payment for {$subscription->plan_name} plan",
            'transaction_date' => now(),
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'payment_status' => 'completed'
        ]);
    }

    public static function recordFeaturedListingPayment($listing, $amount, $paymentMethod = null, $paymentReference = null)
    {
        return self::create([
            'transaction_type' => 'income',
            'category' => 'featured_listing_revenue',
            'amount' => $amount,
            'description' => "Featured listing payment for: {$listing->title}",
            'transaction_date' => now(),
            'user_id' => $listing->user_id,
            'equipment_listing_id' => $listing->id,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'payment_status' => 'completed'
        ]);
    }

    public static function recordBannerPayment($bannerPurchase, $amount, $paymentMethod = null, $paymentReference = null)
    {
        return self::create([
            'transaction_type' => 'income',
            'category' => 'banner_ad_revenue',
            'amount' => $amount,
            'description' => "Banner advertisement payment",
            'transaction_date' => now(),
            'user_id' => $bannerPurchase->user_id,
            'banner_purchase_id' => $bannerPurchase->id,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentReference,
            'payment_status' => 'completed'
        ]);
    }
}
