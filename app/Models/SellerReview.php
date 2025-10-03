<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'reviewer_id',
        'listing_id',
        'rating',
        'review',
        'review_categories',
        'is_verified_purchase',
        'verified_at',
        'seller_reply',
        'seller_replied_at',
    ];

    protected $casts = [
        'review_categories' => 'array',
        'is_verified_purchase' => 'boolean',
        'verified_at' => 'datetime',
        'seller_replied_at' => 'datetime',
    ];

    // Relationships
    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class, 'listing_id');
    }

    // Scopes
    public function scopeVerifiedPurchases($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Boot method to update seller rating when review is created/updated/deleted
    // protected static function booted()
    // {
    //     static::saved(function ($review) {
    //         $review->seller->updateRating();
    //     });

    //     static::deleted(function ($review) {
    //         $review->seller->updateRating();
    //     });
    // }
}