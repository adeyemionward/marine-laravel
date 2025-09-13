<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'user_id',
        'seller_application_id',
        'plan_id',
        'amount',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'invoice_type',
        'discount_type',
        'tax_rate',
        'due_date',
        'notes',
        'terms_and_conditions',
        'items',
        'company_name',
        'generated_by',
        'sent_at',
        'paid_at'
    ];

    protected $casts = [
        'items' => 'array',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sellerApplication(): BelongsTo
    {
        return $this->belongsTo(SellerApplication::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '<', now());
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date < now();
    }

    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->total_amount, 2);
    }

    // Mutators
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'sent_at' => now()
        ]);
    }

    // Static methods
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastInvoice = self::whereDate('created_at', now())->latest()->first();
        $sequence = $lastInvoice ? (int)substr($lastInvoice->invoice_number, -3) + 1 : 1;
        
        return sprintf('%s-%s-%03d', $prefix, $date, $sequence);
    }
}
