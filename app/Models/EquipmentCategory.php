<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EquipmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_name',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    /**
     * Validation rules for creating/updating categories
     */
    public static function rules($id = null)
    {
        return [
            'name' => 'required|string|max:255|unique:equipment_categories,name' . ($id ? ",$id" : ''),
            'slug' => 'nullable|string|max:255|unique:equipment_categories,slug' . ($id ? ",$id" : ''),
            'description' => 'nullable|string',
            'icon_name' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:equipment_categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'parent_id' => 'integer',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EquipmentCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(EquipmentListing::class, 'category_id');
    }

    public function activeListings(): HasMany
    {
        return $this->listings()->where('status', 'active');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildren(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helper Methods
    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getListingsCount(): int
    {
        return $this->listings()->count();
    }

    public function getActiveListingsCount(): int
    {
        return $this->activeListings()->count();
    }

    public function getFullName(): string
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        
        return $this->name;
    }
}