<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Expense extends Model
{
    protected $fillable = [
        'expense_number',
        'amount',
        'category',
        'subcategory',
        'description',
        'expense_date',
        'vendor_name',
        'payment_method',
        'receipt_url',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'attachments',
        'notes',
        'is_recurring',
        'recurring_frequency',
        'recurring_end_date',
        'tax_amount',
        'reference_number'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'approved_at' => 'datetime',
        'recurring_end_date' => 'date',
        'attachments' => 'array',
        'is_recurring' => 'boolean',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2'
    ];

    public static $categories = [
        'Operations' => [
            'Server Hosting',
            'Software Licenses',
            'Third-party Services',
            'Domain & SSL',
            'Cloud Storage',
            'Email Services'
        ],
        'Marketing' => [
            'Digital Advertising',
            'Content Creation',
            'SEO Tools',
            'Social Media',
            'Influencer Partnerships',
            'Event Sponsorship'
        ],
        'Legal & Compliance' => [
            'Legal Fees',
            'Compliance Costs',
            'Insurance',
            'Audit Fees',
            'Trademark & Patents'
        ],
        'Office & Admin' => [
            'Office Supplies',
            'Equipment Purchase',
            'Utilities',
            'Communication',
            'Travel',
            'Training & Development'
        ],
        'Finance' => [
            'Bank Fees',
            'Payment Processing',
            'Accounting Services',
            'Tax Preparation',
            'Currency Exchange'
        ]
    ];

    public static $paymentMethods = [
        'bank_transfer',
        'credit_card',
        'debit_card',
        'cash',
        'paypal',
        'cryptocurrency',
        'check'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            $expense->expense_number = self::generateExpenseNumber();
            $expense->created_by = Auth::id();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now()
        ]);
    }

    public function reject(User $approver, string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => $this->notes ? $this->notes . "\n\nRejection Reason: " . $reason : "Rejection Reason: " . $reason
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->amount + $this->tax_amount;
    }

    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return '₦' . number_format($this->total_amount, 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'approved' && $this->expense_date->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function shouldRecur(): bool
    {
        if (!$this->is_recurring || !$this->recurring_frequency) {
            return false;
        }

        if ($this->recurring_end_date && $this->recurring_end_date->isPast()) {
            return false;
        }

        return true;
    }

    public function getNextRecurrenceDate(): ?Carbon
    {
        if (!$this->shouldRecur()) {
            return null;
        }

        return match ($this->recurring_frequency) {
            'monthly' => $this->expense_date->addMonth(),
            'quarterly' => $this->expense_date->addQuarter(),
            'yearly' => $this->expense_date->addYear(),
            default => null
        };
    }

    public static function generateExpenseNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;
        return "EXP-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public static function getCategoriesList(): array
    {
        return array_keys(self::$categories);
    }

    public static function getSubcategoriesByCategory(string $category): array
    {
        return self::$categories[$category] ?? [];
    }

    public static function getTotalExpensesForPeriod(Carbon $startDate, Carbon $endDate): float
    {
        return self::byDateRange($startDate, $endDate)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');
    }

    public static function getExpensesByCategory(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = self::whereIn('status', ['approved', 'paid']);

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        return $query->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->toArray();
    }
}
