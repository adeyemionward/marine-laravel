<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $fillable = [
        'type', 'status', 'file_path', 'size', 'duration',
        'started_at', 'completed_at', 'created_by'
    ];
}
