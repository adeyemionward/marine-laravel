<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterTemplate extends Model
{
    protected $fillable = [
        'template_name',
        'description',
        'subject_template',
        'html_template',
    ];
}
