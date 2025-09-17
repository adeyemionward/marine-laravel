<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'equipment_listing_id',
        'status',
        'amount',
        'shipping_cost',
        'tax_amount',
        'total_amount',
        'currency',
        'delivery_method',
        'delivery_address',
        'billing_address',
        'estimated_delivery',
        'actual_delivery',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_due_date',
        'paid_at',
        'buyer_notes',
        'seller_notes',
        'admin_notes',
        'tracking_number',
        'status_history',
    ];

    protected $casts = [
        'delivery_address' => 'array',
        'billing_address' => 'array',
        'status_history' => 'array',
        'estimated_delivery' => 'datetime',
        'actual_delivery' => 'datetime',
        'payment_due_date' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function equipmentListing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    // Scopes
    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    // Methods
    public function generateOrderNumber(): string
    {
        return 'ORD-' . date('Y') . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function updateStatus(string $status, string $notes = null): void
    {
        $statusHistory = $this->status_history ?? [];
        $statusHistory[] = [
            'status' => $status,
            'changed_at' => now(),
            'notes' => $notes,
        ];

        $this->update([
            'status' => $status,
            'status_history' => $statusHistory,
        ]);
    }

    public function markAsPaid(string $paymentReference = null): void
    {
        $this->update([
            'payment_status' => 'completed',
            'payment_reference' => $paymentReference,
            'paid_at' => now(),
        ]);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'processing' => 'indigo',
            'shipped' => 'purple',
            'delivered' => 'green',
            'cancelled' => 'red',
            'disputed' => 'orange',
            default => 'gray',
        };
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'refunded' => 'purple',
            default => 'gray',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = $order->generateOrderNumber();
            }
        });
    }
}
