<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tier',
        'description',
        'price',
        'billing_cycle',
        'features',
        'limits',
        'max_listings',
        'max_images_per_listing',
        'priority_support',
        'analytics_access',
        'custom_branding',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'tier' => \App\Enums\SubscriptionTier::class,
        'billing_cycle' => \App\Enums\BillingCycle::class,
        'max_listings' => 'integer',
        'max_images_per_listing' => 'integer',
        'priority_support' => 'boolean',
        'analytics_access' => 'boolean',
        'custom_branding' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id')
            ->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTier($query, $tier)
    {
        return $query->where('tier', $tier);
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₦' . number_format($this->price, 2);
    }

    public function isUnlimited(string $feature): bool
    {
        $limits = $this->limits ?? [];
        return isset($limits[$feature]) && $limits[$feature] === -1;
    }

    public function getLimit(string $feature): ?int
    {
        $limits = $this->limits ?? [];
        return $limits[$feature] ?? null;
    }
}