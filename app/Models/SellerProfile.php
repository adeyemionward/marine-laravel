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
        $count = $this->listings()->where('status', 'active')->count();
        $this->update(['total_listings' => $count]);
    }
}