<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'service',
        'api_key',
        'secret_key',
        'config',
        'status',
        'last_tested_at',
        'test_result',
        'description'
    ];

    protected $casts = [
        'config' => 'array',
        'test_result' => 'array',
        'last_tested_at' => 'datetime'
    ];

    protected $hidden = [
        'api_key',
        'secret_key'
    ];

    public function getDecryptedApiKeyAttribute()
    {
        return decrypt($this->api_key);
    }

    public function getDecryptedSecretKeyAttribute()
    {
        return $this->secret_key ? decrypt($this->secret_key) : null;
    }

    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = encrypt($value);
    }

    public function setSecretKeyAttribute($value)
    {
        $this->attributes['secret_key'] = $value ? encrypt($value) : null;
    }
}
