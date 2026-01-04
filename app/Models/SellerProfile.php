<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_description',
        'specialties',
        'years_active',
        'rating',
        'review_count',
        'total_listings',
        'response_time',
        'avg_response_minutes',
        'verification_documents',
        'verification_status',
        'verification_notes',
        'verified_at',
        'is_featured',
        'featured_priority',
        'business_hours',
        'website',
        'social_media',
    ];

    protected $casts = [
        'specialties' => 'array',
        'verification_documents' => 'array',
        'business_hours' => 'array',
        'social_media' => 'array',
        'rating' => 'decimal:2',
        'is_featured' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $appends = [
        'location',
        'profile_image',
        'avatar_url',
        'full_name',
        'is_verified',
        'average_rating',
        'reviews_count',
        'featured_service',
        'since',
        'starting_price',
        'level',
        'skills',
        'response_rate',
        'whatsapp',
        'total_orders',
        'delivery_time',
        'languages',
        'certifications',
        'online_status',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_id', 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SellerReview::class, 'seller_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(EquipmentListing::class, 'seller_id', 'user_id');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->orderBy('featured_priority', 'desc');
    }

    public function scopeTopRated($query, $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating)
                    ->where('review_count', '>', 0)
                    ->orderBy('rating', 'desc');
    }

    public function scopeBySpecialty($query, $specialty)
    {
        return $query->whereJsonContains('specialties', $specialty);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->whereHas('userProfile', function ($q) use ($location) {
            $q->where('city', 'like', "%{$location}%")
              ->orWhere('state', 'like', "%{$location}%");
        });
    }

    public function scopeFastResponse($query, $maxMinutes = 120)
    {
        return $query->where('avg_response_minutes', '<=', $maxMinutes);
    }

    // Accessors
    public function getLocationAttribute(): string
    {
        $profile = $this->userProfile;
        if (!$profile) return 'Nigeria';

        $location = $profile->city;
        if ($profile->state) {
            $location .= ', ' . $profile->state;
        }
        return $location ?: 'Nigeria';
    }

    public function getProfileImageAttribute(): ?string
    {
        return $this->userProfile?->avatar_url;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->userProfile?->avatar_url;
    }

    public function getFullNameAttribute(): ?string
    {
        return $this->userProfile?->full_name;
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function getAverageRatingAttribute(): float
    {
        return (float) $this->rating;
    }

    public function getReviewsCountAttribute(): int
    {
        return (int) $this->review_count;
    }

    public function getFeaturedServiceAttribute(): ?string
    {
        // Return the first specialty or a generic service
        $specialties = $this->specialties ?? [];
        return !empty($specialties) ? "Professional " . $specialties[0] . " Services" : "Marine Equipment Services";
    }

    public function getSinceAttribute(): string
    {
        // Format the year when seller was verified or created
        $date = $this->verified_at ?? $this->created_at;
        return $date ? $date->format('Y') : date('Y');
    }

    public function getStartingPriceAttribute(): float
    {
        // Get the minimum price from active listings
        $minPrice = $this->listings()
            ->where('status', 'active')
            ->whereNotNull('price')
            ->where('is_poa', false)
            ->min('price');

        return $minPrice ?? 0;
    }

    public function getLevelAttribute(): ?string
    {
        // Determine seller level based on rating and reviews
        if ($this->rating >= 4.8 && $this->review_count >= 50) {
            return 'top_rated';
        } elseif ($this->rating >= 4.5 && $this->review_count >= 20) {
            return 'level_2';
        }
        return null;
    }

    public function getSkillsAttribute(): array
    {
        // Map specialties to skills
        return $this->specialties ?? [];
    }

    public function getResponseRateAttribute(): int
    {
        // Default response rate (could be calculated from actual data)
        return 95;
    }

    public function getWhatsappAttribute(): ?string
    {
        // Get WhatsApp from user profile phone
        return $this->userProfile?->phone;
    }

    public function getTotalOrdersAttribute(): int
    {
        // Count completed orders (if orders table exists)
        try {
            return \DB::table('orders')
                ->where('seller_id', $this->user_id)
                ->where('status', 'completed')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getDeliveryTimeAttribute(): string
    {
        // Default delivery time
        return '2-3 days';
    }

    public function getLanguagesAttribute(): array
    {
        // Default languages
        return ['English'];
    }

    public function getCertificationsAttribute(): array
    {
        // Parse from verification documents or return empty
        return [];
    }

    public function getOnlineStatusAttribute(): bool
    {
        // Check if user was active recently (within last 15 minutes)
        if ($this->user) {
            return $this->user->last_activity_at &&
                   $this->user->last_activity_at->diffInMinutes(now()) < 15;
        }
        return false;
    }

    // Helper Methods
    public function isVerified(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function updateRating(): void
    {
        $reviews = $this->reviews;
        $this->update([
            'rating' => $reviews->avg('rating') ?? 0,
            'review_count' => $reviews->count(),
        ]);
    }

    public function updateListingCount(): void
    {
        $count = $this->listings()
            ->whereNotIn('status', ['archived', 'rejected'])
            ->count();
        $this->update(['total_listings' => $count]);
    }
}