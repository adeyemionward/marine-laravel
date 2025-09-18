<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    protected $fillable = [
        'title',
        'template_id',
        'use_default_template',
        'schedule_for',
    ];

    protected $casts = [
        'use_default_template' => 'boolean',
        'schedule_for' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(NewsletterTemplate::class, 'template_id');
    }
}
