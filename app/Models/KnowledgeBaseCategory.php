<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class KnowledgeBaseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'created_by',
    ];

    /**
     * Validation rules for creating/updating categories
     */
    public static function rules($id = null)
    {
        return [
            'name' => 'required|string|max:255|unique:knowledge_base_categories,name' . ($id ? ",$id" : ''),
            'slug' => 'nullable|string|max:255|unique:knowledge_base_categories,slug' . ($id ? ",$id" : ''),
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'created_by' => 'required|exists:users,id',
        ];
    }

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KnowledgeBaseDocument::class, 'category_id');
    }

    public function publishedDocuments(): HasMany
    {
        return $this->documents()->where('status', 'published');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
