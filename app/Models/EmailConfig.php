<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailConfig extends Model
{
    protected $fillable = [
        'driver', 'smtp_host', 'smtp_port', 'username', 'password',
        'from_email', 'from_name', 'encryption', 'enable_smtp',
    ];

    protected $hidden = ['password']; 
}
