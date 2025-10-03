<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_listing_id',
        'user_id',
        'rating',
        'title',
        'comment',
        'images',
        'is_verified_purchase',
        'status',
        'seller_reply',
        'seller_replied_at',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'images' => 'array',
        'is_verified_purchase' => 'boolean',
        'seller_replied_at' => 'datetime',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'rating' => 'integer',
    ];

    protected $appends = ['average_helpfulness'];

    /**
     * Get the equipment listing that owns the review
     */
    public function equipmentListing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class);
    }

    /**
     * Get the user who wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get approved reviews only
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get verified purchase reviews
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Get the average helpfulness ratio
     */
    public function getAverageHelpfulnessAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return 0;
        }
        return round(($this->helpful_count / $total) * 100, 2);
    }

    /**
     * Check if user has already marked this review as helpful/not helpful
     */
    public function hasUserVoted(int $userId): bool
    {
        // You can implement a separate review_votes table for this
        // For now, we'll skip this check
        return false;
    }
}
