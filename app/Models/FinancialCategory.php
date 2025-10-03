<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialCategory extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'color',
        'is_system',
        'is_active'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter system categories
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to filter custom categories
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Get transactions for this category
     */
    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'category', 'name');
    }

    /**
     * Get transaction count for this category
     */
    public function getTransactionCountAttribute()
    {
        return $this->transactions()->count();
    }

    /**
     * Get total amount for this category
     */
    public function getTotalAmountAttribute()
    {
        return $this->transactions()->sum('amount');
    }
}
