<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\UserRole;
use App\Traits\HasUuids;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'role',
        'is_active',
        'company_name',
        'company_description',
        'bio',
        'website',
        'linkedin',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'avatar',
        'verification_documents',
        'is_verified',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_backup_codes',
    ];

    protected $casts = [
        'role' => UserRole::class,
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'verification_documents' => 'array',
        'two_factor_backup_codes' => 'array',
        'email_verified_at' => 'datetime',
    ];

    protected $dates = [
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(EquipmentListing::class, 'seller_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class, 'user_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id')
            ->orWhere('seller_id', $this->id);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'inquirer_id');
    }

    public function createdBanners(): HasMany
    {
        return $this->hasMany(Banner::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByRole($query, UserRole $role)
    {
        return $query->where('role', $role->value);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', UserRole::ADMIN->value);
    }

    // Accessors & Mutators
    public function getFullNameAttribute($value): string
    {
        return ucwords(strtolower($value));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return asset('storage/avatars/' . $this->avatar);
    }

    // Helper Methods
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::MODERATOR;
    }

    public function canManageListings(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::MODERATOR]);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }
}