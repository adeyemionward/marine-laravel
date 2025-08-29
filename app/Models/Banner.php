<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'position',
        'media_type',
        'media_url',
        'link_url',
        'priority',
        'status',
        'start_date',
        'end_date',
        'click_count',
        'impression_count',
        'revenue_earned',
        'created_by',
    ];

    protected $casts = [
        'priority' => 'integer',
        'click_count' => 'integer',
        'impression_count' => 'integer',
        'revenue_earned' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->where('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function scopePosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    public function incrementImpressions(): void
    {
        $this->increment('impressions');
    }

    public function getClickThroughRateAttribute(): float
    {
        return $this->impressions > 0 ? 
            round(($this->clicks / $this->impressions) * 100, 2) : 0;
    }

    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        
        if ($this->starts_at && $this->starts_at > $now) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < $now) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isScheduled(): bool
    {
        return $this->starts_at && $this->starts_at > now();
    }
}