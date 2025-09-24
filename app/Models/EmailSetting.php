<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = [
        'driver',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_email',
        'from_name',
        'is_active',
        'use_tls',
        'configuration',
        'tested_at',
        'test_result',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_tls' => 'boolean',
        'configuration' => 'array',
        'tested_at' => 'datetime',
        'port' => 'integer',
    ];

    const DRIVER_SMTP = 'smtp';
    const DRIVER_GMAIL = 'gmail';
    const DRIVER_OUTLOOK = 'outlook';
    const DRIVER_CUSTOM = 'custom';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getIsTestedAttribute()
    {
        return !is_null($this->tested_at);
    }

    public function getLastTestStatusAttribute()
    {
        if (!$this->is_tested) {
            return 'not_tested';
        }

        return $this->test_result === 'success' ? 'success' : 'failed';
    }
}
