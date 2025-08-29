<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'user_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'started_at',
        'expires_at',
        'cancelled_at',
        'auto_renew',
        'stripe_subscription_id',
        'stripe_customer_id',
        'payment_method_id',
        'trial_ends_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('expires_at', '<=', now());
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at <= now();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function daysUntilExpiry(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->expires_at);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount_paid, 2) . ' ' . strtoupper($this->currency);
    }

    public function canRenew(): bool
    {
        return in_array($this->status, ['active', 'expired']) && $this->auto_renew;
    }

    public function markAsExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);
    }

    public function renew(int $days = 30): bool
    {
        $newExpiryDate = $this->isExpired() ? now()->addDays($days) : $this->expires_at->addDays($days);

        return $this->update([
            'status' => 'active',
            'expires_at' => $newExpiryDate,
        ]);
    }
}