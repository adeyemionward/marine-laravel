<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'html_content',
        'thumbnail',
        'category',
        'is_active',
        // Legacy fields
        'template_name',
        'subject_template',
        'html_template',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function newsletters()
    {
        return $this->hasMany(Newsletter::class, 'template_id');
    }
}
