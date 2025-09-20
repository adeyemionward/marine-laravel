<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class KnowledgeBaseDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'document_type',
        'status',
        'tags',
        'category_id',
        'created_by',
        'updated_by',
        'published_at',
        'view_count',
        'sort_order',
        'is_featured',
        'meta_data',
    ];

    protected $casts = [
        'tags' => 'array',
        'meta_data' => 'array',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (empty($document->slug)) {
                $document->slug = Str::slug($document->title);
            }
        });

        static::updating(function ($document) {
            if ($document->isDirty('title') && empty($document->slug)) {
                $document->slug = Str::slug($document->title);
            }

            if ($document->isDirty('status') && $document->status === 'published' && !$document->published_at) {
                $document->published_at = now();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function userProfiles(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'created_by', 'user_id');
    }

    public function knowledgeBaseCategories(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('published_at');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('summary', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getRelatedDocuments($limit = 4)
    {
        return self::published()
            ->where('id', '!=', $this->id)
            ->where('category_id', $this->category_id)
            ->with(['category', 'userProfiles'])
            ->ordered()
            ->limit($limit)
            ->get();
    }
}
