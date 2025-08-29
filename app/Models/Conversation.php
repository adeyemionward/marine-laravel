<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'buyer_id',
        'seller_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('buyer_id', $userId)
              ->orWhere('seller_id', $userId);
        });
    }

    public function getOtherParticipant($currentUserId): UserProfile
    {
        return $this->buyer_id === $currentUserId ? $this->seller : $this->buyer;
    }

    public function getUnreadCount($currentUserId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $currentUserId)
            ->where('is_read', false)
            ->count();
    }

    public function markMessagesAsRead($currentUserId): void
    {
        $this->messages()
            ->where('sender_id', '!=', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}