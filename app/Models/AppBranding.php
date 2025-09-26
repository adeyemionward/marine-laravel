<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBranding extends Model
{
    protected $fillable = ['app_name', 'primary_logo', 'admin_logo'];
}
