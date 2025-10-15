<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens,HasRoles;
     protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    const ACTIVE     = 1;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the user's role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        if (!$this->role) return false;
        return in_array($this->role->name, $roles);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    /**
     * Get user's role name.
     */
    public function getRoleName(): ?string
    {
        return $this->role?->name;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a seller.
     * NEW RULE: Users can only be sellers if they have both the seller role AND a seller profile.
     */
    public function isSeller(): bool
    {
        return $this->hasRole('seller') && $this->sellerProfile()->exists();
    }

    /**
     * Check if user is a regular user.
     */
    public function isUser(): bool
    {
        return $this->hasRole('user');
    }

    /**
     * Get user's seller profile.
     */
    public function sellerProfile(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    /**
     * Get user's subscriptions through profile.
     */
    public function subscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Subscription::class,
            UserProfile::class,
            'user_id', // Foreign key on UserProfile table
            'user_id', // Foreign key on Subscription table
            'id',      // Local key on User table
            'id'       // Local key on UserProfile table
        );
    }

    /**
     * Get user's subscription (for single subscription access).
     */
    public function subscription()
    {
        return $this->subscriptions()->first();
    }

    /**
     * Get user's active subscription.
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->with('plan')
            ->active()
            ->first();
    }

    /**
     * Get user's listings.
     */
    public function listings(): HasMany
    {
        return $this->hasMany(EquipmentListing::class, 'seller_id');
    }

    /**
     * Get user's orders as buyer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    /**
     * Get user's sales as seller.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    /**
     * Get user's payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get user's invoices.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Promote user to seller by creating seller profile and updating role.
     * This is the ONLY way a user should become a seller.
     */
    public function promoteToSeller(array $sellerData = []): bool
    {
        try {
            DB::transaction(function () use ($sellerData) {
                // Create seller profile first
                $this->sellerProfile()->create(array_merge([
                    'business_name' => $this->name,
                    'business_type' => 'Individual',
                    'verification_status' => 'pending',
                    'status' => 'active',
                ], $sellerData));

                // Then update role
                $sellerRole = Role::where('name', 'seller')->first();
                if ($sellerRole) {
                    $this->update(['role_id' => $sellerRole->id]);
                }
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to promote user to seller: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Demote seller to regular user by removing seller profile and updating role.
     */
    public function demoteFromSeller(): bool
    {
        try {
            DB::transaction(function () {
                // Delete seller profile first
                $this->sellerProfile()->delete();

                // Then update role
                $userRole = Role::where('name', 'user')->first();
                if ($userRole) {
                    $this->update(['role_id' => $userRole->id]);
                }
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to demote seller to user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has access based on email and active status.
     * Admin email bypasses all checks.
     */


    public function hasAccess($access)
    {
        if ($this->email === 'admin@marine.ng') return true;
        if ($this->active_status != self::ACTIVE) return false;

        // Make sure the trait is present
        if (!$this->hasPermissionTo($access)) {
            return false;
        }

        return true;
    }

    //  public function hasAccess($access)
    // {
    //     if($this->email == 'admin@gmail.com') return true;
    //     if($this->active_status != User::ACTIVE){
    //             return 'yy';
    //     } //Account not active
    //     if (!auth()->user() || !auth()->user()->hasPermissionTo($access, 'api')) {
    //         return false;
    //     }
    //     return true;
    // }
}
