<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = [
        'listing_id',
        'inquirer_id',
        'inquirer_name',
        'inquirer_email',
        'inquirer_phone',
        'subject',
        'message',
        'status',
        'admin_notes',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class, 'listing_id');
    }

    public function inquirer(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'inquirer_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    public function markAsResponded(): void
    {
        $this->update([
            'status' => 'responded',
            'responded_at' => now(),
        ]);
    }
}
