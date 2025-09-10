<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_description',
        'business_registration_number',
        'tax_identification_number',
        'business_documents',
        'specialties',
        'years_experience',
        'previous_platforms',
        'motivation',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'business_documents' => 'array',
        'specialties' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helper Methods
    public function approve(User $reviewer, ?string $notes = null): bool
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        // Create or update seller profile
        $sellerProfile = SellerProfile::updateOrCreate(
            ['user_id' => $this->user_id],
            [
                'business_name' => $this->business_name,
                'business_description' => $this->business_description,
                'specialties' => $this->specialties,
                'years_active' => $this->years_experience,
                'verification_status' => 'approved',
                'verified_at' => now(),
                'verification_documents' => $this->business_documents,
            ]
        );

        // Update user role to seller and set seller_profile_id
        $this->user->update([
            'role' => 'seller',
            'seller_profile_id' => $sellerProfile->id,
        ]);

        // Update user profile verification status
        $this->user->profile->update([
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        return true;
    }

    public function reject(User $reviewer, string $reason): bool
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'admin_notes' => $reason,
        ]);

        return true;
    }
}