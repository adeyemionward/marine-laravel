<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Newsletter extends Model
{
    protected $fillable = [
        'title',
        'content',
        'excerpt',
        'status',
        'scheduled_at',
        'sent_at',
        'recipient_count',
        'open_count',
        'click_count',
        'tags',
        'template_id',
        'recipients',
        'subject',
        'from_email',
        'from_name',
        'template_data',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'recipients' => 'array',
        'tags' => 'array',
        'recipient_count' => 'integer',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'template_data' => 'array',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENT = 'sent';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'created_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                    ->where('scheduled_at', '<=', now());
    }

    public function getOpenRateAttribute()
    {
        return $this->recipient_count > 0 ?
            round(($this->open_count / $this->recipient_count) * 100, 2) : 0;
    }

    public function getClickRateAttribute()
    {
        return $this->open_count > 0 ?
            round(($this->click_count / $this->open_count) * 100, 2) : 0;
    }

    public function template()
    {
        return $this->belongsTo(NewsletterTemplate::class);
    }
}
