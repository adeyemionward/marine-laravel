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
        'purchaser_id',
        'purchase_price',
        'purchase_status',
        'purchased_at',
        'pricing_details',
        'payment_reference',
        'banner_type',
        'duration_days',
        'auto_approve',
        'admin_notes',
        'banner_size',
        'dimensions',
        'mobile_dimensions',
        'display_context',
        'sort_order',
        'show_on_mobile',
        'show_on_desktop',
        'target_category_id',
        'target_locations',
        'user_target',
        'background_color',
        'text_color',
        'button_text',
        'button_color',
        'overlay_settings',
        'conversion_rate',
        'max_impressions',
        'max_clicks',
    ];

    protected $casts = [
        'priority' => 'integer',
        'click_count' => 'integer',
        'impression_count' => 'integer',
        'revenue_earned' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'purchase_price' => 'decimal:2',
        'purchased_at' => 'datetime',
        'pricing_details' => 'array',
        'duration_days' => 'integer',
        'auto_approve' => 'boolean',
        'dimensions' => 'array',
        'mobile_dimensions' => 'array',
        'sort_order' => 'integer',
        'show_on_mobile' => 'boolean',
        'show_on_desktop' => 'boolean',
        'target_locations' => 'array',
        'overlay_settings' => 'array',
        'conversion_rate' => 'decimal:2',
        'max_impressions' => 'integer',
        'max_clicks' => 'integer',
    ];

    // Banner Position Constants
    const POSITION_HERO = 'hero';
    const POSITION_CATEGORY_ROW = 'category_row';
    const POSITION_PRODUCT_PROMOTION = 'product_promotion';
    const POSITION_SIDEBAR = 'sidebar';
    const POSITION_FOOTER = 'footer';
    const POSITION_LISTING_TOP = 'listing_top';
    const POSITION_LISTING_BOTTOM = 'listing_bottom';
    const POSITION_DETAIL_SIDEBAR = 'detail_sidebar';
    const POSITION_SEARCH_TOP = 'search_top';

    // Banner Size Constants
    const SIZE_SMALL = 'small';           // 300x200
    const SIZE_MEDIUM = 'medium';         // 600x300
    const SIZE_LARGE = 'large';           // 800x400
    const SIZE_FULL_WIDTH = 'full_width'; // 1920x400

    // Display Context Constants
    const CONTEXT_HOMEPAGE = 'homepage';
    const CONTEXT_CATEGORY = 'category';
    const CONTEXT_LISTING_DETAIL = 'listing_detail';
    const CONTEXT_SEARCH = 'search';
    const CONTEXT_SELLER_PROFILE = 'seller_profile';

    // Banner Type Constants
    const TYPE_PROMOTIONAL = 'promotional';
    const TYPE_SPONSORED = 'sponsored';
    const TYPE_CATEGORY = 'category';
    const TYPE_FEATURED = 'featured';
    const TYPE_SERVICE = 'service';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'created_by');
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchaser_id');
    }

    public function targetCategory(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'target_category_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
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

    public function isPaid(): bool
    {
        return $this->purchase_status === 'paid';
    }

    public function isPendingPayment(): bool
    {
        return $this->purchase_status === 'pending_payment';
    }

    public function isCancelled(): bool
    {
        return $this->purchase_status === 'cancelled';
    }

    public function markAsPaid(): void
    {
        $this->update([
            'purchase_status' => 'paid',
            'purchased_at' => now(),
        ]);
    }

    public function calculateEndDate(): \Carbon\Carbon
    {
        $startDate = $this->start_date ?? now();
        return $startDate->addDays($this->duration_days ?? 30);
    }

    public function scopePaid($query)
    {
        return $query->where('purchase_status', 'paid');
    }

    public function scopePendingPayment($query)
    {
        return $query->where('purchase_status', 'pending_payment');
    }

    public function scopeForBannerType($query, $type)
    {
        return $query->where('banner_type', $type);
    }

    public function scopeForContext($query, $context)
    {
        return $query->where('display_context', $context);
    }

    public function scopeForPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeForDevice($query, $device = 'desktop')
    {
        $column = $device === 'mobile' ? 'show_on_mobile' : 'show_on_desktop';
        return $query->where($column, true);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where(function($q) use ($categoryId) {
            $q->whereNull('target_category_id')
              ->orWhere('target_category_id', $categoryId);
        });
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('sort_order', 'asc')
                    ->orderBy('priority', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    // Helper methods
    public static function getPositions()
    {
        return [
            self::POSITION_HERO => 'Hero Banner',
            self::POSITION_CATEGORY_ROW => 'Category Row',
            self::POSITION_PRODUCT_PROMOTION => 'Product Promotion',
            self::POSITION_SIDEBAR => 'Sidebar',
            self::POSITION_FOOTER => 'Footer',
            self::POSITION_LISTING_TOP => 'Listing Top',
            self::POSITION_LISTING_BOTTOM => 'Listing Bottom',
            self::POSITION_DETAIL_SIDEBAR => 'Detail Sidebar',
            self::POSITION_SEARCH_TOP => 'Search Top',
        ];
    }

    public static function getSizes()
    {
        return [
            self::SIZE_SMALL => ['width' => 300, 'height' => 200],
            self::SIZE_MEDIUM => ['width' => 600, 'height' => 300],
            self::SIZE_LARGE => ['width' => 800, 'height' => 400],
            self::SIZE_FULL_WIDTH => ['width' => 1920, 'height' => 400],
        ];
    }

    public static function getContexts()
    {
        return [
            self::CONTEXT_HOMEPAGE => 'Homepage',
            self::CONTEXT_CATEGORY => 'Category Page',
            self::CONTEXT_LISTING_DETAIL => 'Listing Detail',
            self::CONTEXT_SEARCH => 'Search Results',
            self::CONTEXT_SELLER_PROFILE => 'Seller Profile',
        ];
    }

    public function getDimensionsForSize()
    {
        if ($this->dimensions) {
            return $this->dimensions;
        }

        $sizes = self::getSizes();
        return $sizes[$this->banner_size] ?? $sizes[self::SIZE_LARGE];
    }

    public function getMobileDimensionsForSize()
    {
        if ($this->mobile_dimensions) {
            return $this->mobile_dimensions;
        }

        // Default mobile dimensions based on size
        $mobileDefaults = [
            self::SIZE_SMALL => ['width' => 300, 'height' => 150],
            self::SIZE_MEDIUM => ['width' => 375, 'height' => 200],
            self::SIZE_LARGE => ['width' => 375, 'height' => 250],
            self::SIZE_FULL_WIDTH => ['width' => 375, 'height' => 200],
        ];

        return $mobileDefaults[$this->banner_size] ?? $mobileDefaults[self::SIZE_LARGE];
    }

    public function hasReachedMaxImpressions()
    {
        return $this->max_impressions && $this->impression_count >= $this->max_impressions;
    }

    public function hasReachedMaxClicks()
    {
        return $this->max_clicks && $this->click_count >= $this->max_clicks;
    }

    public function updateConversionRate()
    {
        if ($this->impression_count > 0) {
            $this->conversion_rate = round(($this->click_count / $this->impression_count) * 100, 2);
            $this->save();
        }
    }
}