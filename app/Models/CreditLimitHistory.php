<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditLimitHistory extends Model
{
    use HasFactory;

    protected $table = 'credit_limit_history';

    protected $fillable = [
        'user_id',
        'old_limit',
        'new_limit',
        'change_type',
        'changed_by',
        'reason',
    ];

    protected $casts = [
        'old_limit' => 'decimal:2',
        'new_limit' => 'decimal:2',
    ];

    /**
     * Get the customer whose limit was changed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who changed the limit
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Calculate the change amount
     */
    public function getChangeAmountAttribute()
    {
        return $this->new_limit - $this->old_limit;
    }

    /**
     * Get the change percentage
     */
    public function getChangePercentageAttribute()
    {
        if ($this->old_limit == 0) {
            return 100;
        }
        return (($this->new_limit - $this->old_limit) / $this->old_limit) * 100;
    }
}
