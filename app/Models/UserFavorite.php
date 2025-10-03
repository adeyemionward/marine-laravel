<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'listing_id',
    ];

    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function isFavorited($userId, $listingId): bool
    {
        return static::where('user_id', $userId)
            ->where('listing_id', $listingId)
            ->exists();
    }

    public static function toggle($userId, $listingId): bool
    {
        $favorite = static::where('user_id', $userId)
            ->where('listing_id', $listingId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return false; // Removed from favorites
        } else {
            static::create([
                'user_id' => $userId,
                'listing_id' => $listingId,
            ]);
            return true; // Added to favorites
        }
    }
}