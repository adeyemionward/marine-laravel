<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'status',
        'attachments',
        'offer_price',
        'offer_currency',
        'is_system',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'attachments' => 'array',
        'offer_price' => 'decimal:2',
        'is_system' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'sender_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForSender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update([
                'read_at' => now(),
            ]);
        }
    }

    public function isFromUser($userId): bool
    {
        return $this->sender_id === $userId;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Update conversation's last_message_at timestamp
            $message->conversation->update([
                'last_message_at' => $message->created_at,
            ]);
        });
    }
}