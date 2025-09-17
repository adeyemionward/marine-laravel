<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerPricing extends Model
{
    use HasFactory;

    protected $table = 'banner_pricing';

    protected $fillable = [
        'banner_type',
        'position',
        'duration_type',
        'duration_value',
        'base_price',
        'premium_multiplier',
        'discount_tiers',
        'is_active',
        'max_concurrent',
        'description',
        'specifications',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'premium_multiplier' => 'decimal:2',
        'discount_tiers' => 'array',
        'specifications' => 'array',
        'is_active' => 'boolean',
        'duration_value' => 'integer',
        'max_concurrent' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('banner_type', $type);
    }

    public function calculatePrice($duration = null, $isPremium = false)
    {
        $duration = $duration ?? $this->duration_value;
        $basePrice = $this->base_price;
        
        if ($isPremium) {
            $basePrice *= $this->premium_multiplier;
        }
        
        // Apply duration multiplier
        $totalPrice = $basePrice * $duration;
        
        // Apply volume discounts if available
        if ($this->discount_tiers && $duration > 1) {
            foreach ($this->discount_tiers as $tier) {
                if ($duration >= $tier['min_duration']) {
                    $discount = $tier['discount_percentage'] ?? 0;
                    $totalPrice *= (1 - ($discount / 100));
                }
            }
        }
        
        return round($totalPrice, 2);
    }

    public function getDurationInDays()
    {
        switch ($this->duration_type) {
            case 'daily':
                return $this->duration_value;
            case 'weekly':
                return $this->duration_value * 7;
            case 'monthly':
                return $this->duration_value * 30;
            default:
                return 30;
        }
    }
}