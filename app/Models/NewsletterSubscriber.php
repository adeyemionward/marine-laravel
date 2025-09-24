<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'status',
        'source',
        'preferences',
        'subscribed_at',
        'unsubscribed_at',
        'unsubscribe_token'
    ];

    protected $casts = [
        'preferences' => 'array',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscriber) {
            if (!$subscriber->unsubscribe_token) {
                $subscriber->unsubscribe_token = Str::random(32);
            }
        });
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function unsubscribe()
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now()
        ]);
    }

    public function resubscribe()
    {
        $this->update([
            'status' => 'active',
            'unsubscribed_at' => null
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSubscribed($query)
    {
        return $query->whereIn('status', ['active']);
    }

    public static function getActiveCount()
    {
        return static::active()->count();
    }

    public static function getTotalCount()
    {
        return static::count();
    }
}