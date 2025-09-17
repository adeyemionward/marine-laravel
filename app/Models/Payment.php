<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reference',
        'transaction_id',
        'user_id',
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_reference',
        'gateway_response',
        'payment_method',
        'payment_details',
        'customer_email',
        'customer_phone',
        'gateway_fee',
        'platform_fee',
        'net_amount',
        'initiated_at',
        'completed_at',
        'failed_at',
        'failure_reason',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'payment_details' => 'array',
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    // Methods
    public function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper(Str::random(10)) . '-' . time();
    }

    public function markAsCompleted($gatewayResponse = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'gateway_response' => $gatewayResponse,
        ]);

        // Update the related payable (order, invoice, etc.)
        if ($this->payable && method_exists($this->payable, 'markAsPaid')) {
            $this->payable->markAsPaid($this->payment_reference);
        }

        // Process invoice workflow if payment is for an invoice
        if ($this->payable instanceof \App\Models\Invoice) {
            try {
                $invoiceWorkflowService = app(\App\Services\InvoiceWorkflowService::class);
                $invoiceWorkflowService->processInvoicePayment($this->payable, $this);
            } catch (\Exception $e) {
                \Log::error('Failed to process invoice payment workflow', [
                    'payment_id' => $this->id,
                    'invoice_id' => $this->payable->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function markAsFailed($reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function processRefund($amount = null, $reason = null): void
    {
        $refundAmount = $amount ?? $this->amount;
        
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_amount' => $refundAmount,
            'refund_reason' => $reason,
        ]);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            'refunded' => 'purple',
            default => 'gray',
        };
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_reference)) {
                $payment->payment_reference = $payment->generatePaymentReference();
            }
        });
    }
}
