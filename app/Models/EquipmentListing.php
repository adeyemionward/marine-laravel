<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\ListingStatus;
use App\Enums\EquipmentCondition;
use App\Enums\ListingType;
use App\Models\User;

class EquipmentListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'listing_type',
        'title',
        'description',
        'brand',
        'model',
        'year',
        'condition',
        'price',
        'currency',
        'is_price_negotiable',
        'is_poa',
        'specifications',
        'features',
        'location_state',
        'location_city',
        'location_address',
        'coordinates',
        'hide_address',
        'delivery_available',
        'delivery_radius',
        'delivery_fee',
        'contact_phone',
        'contact_email',
        'contact_whatsapp',
        'contact_methods',
        'availability_hours',
        'allows_inspection',
        'allows_test_drive',
        'status',
        'is_featured',
        'priority',
        'featured_until',
        'is_verified',
        'view_count',
        'inquiry_count',
        'images',
        'tags',
        'seo_title',
        'seo_description',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'condition' => EquipmentCondition::class,
        'status' => ListingStatus::class,
        'listing_type' => ListingType::class,
        'price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'is_price_negotiable' => 'boolean',
        'is_poa' => 'boolean',
        'hide_address' => 'boolean',
        'delivery_available' => 'boolean',
        'allows_inspection' => 'boolean',
        'allows_test_drive' => 'boolean',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'specifications' => 'array',
        'features' => 'array',
        'contact_methods' => 'array',
        'availability_hours' => 'array',
        'images' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'featured_until' => 'datetime',
        'year' => 'integer',
        'view_count' => 'integer',
        'inquiry_count' => 'integer',
        'delivery_radius' => 'integer',
    ];

    // Relationships
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'category_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class, 'listing_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'listing_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'listing_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(EquipmentReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(EquipmentReview::class)->where('status', 'approved');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::ACTIVE);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeInLocation(Builder $query, string $state, string $city = null): Builder
    {
        $query->where('location_state', $state);
        
        if ($city) {
            $query->where('location_city', $city);
        }

        return $query;
    }

    public function scopePriceRange(Builder $query, float $min = null, float $max = null): Builder
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    public function scopeByCondition(Builder $query, EquipmentCondition $condition): Builder
    {
        return $query->where('condition', $condition->value);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Accessors & Mutators
    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_poa) {
            return 'Price on Application';
        }

        if (!$this->price) {
            return 'Contact for Price';
        }

        return $this->currency . ' ' . number_format($this->price, 2);
    }

    public function getPrimaryImageAttribute(): ?string
    {
        $images = $this->images ?? [];
        return !empty($images) ? $images[0] : null;
    }

    public function getSlugAttribute(): string
    {
        return \Illuminate\Support\Str::slug($this->title . '-' . $this->id);
    }

    // Helper Methods
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementInquiryCount(): void
    {
        $this->increment('inquiry_count');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeContacted(): bool
    {
        return $this->status === ListingStatus::ACTIVE && !$this->isExpired();
    }

    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    public function publish(): void
    {
        $this->update([
            'status' => ListingStatus::ACTIVE,
            'published_at' => now(),
        ]);
    }

    public function markAsSold(): void
    {
        $this->update(['status' => ListingStatus::SOLD]);
    }
}