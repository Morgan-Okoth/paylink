<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'user_id',
        'customer_id',
        'invoice_number',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'mpesa_checkout_request_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function markAsPaid(string $mpesaReceiptNumber = null): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'mpesa_checkout_request_id' => $mpesaReceiptNumber ?? $this->mpesa_checkout_request_id,
        ]);

        return true;
    }

    public function markAsOverdue(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update(['status' => self::STATUS_OVERDUE]);

        return true;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->status === self::STATUS_PENDING && $this->due_date->isPast());
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)
                     ->orWhere(function ($q) {
                         $q->where('status', self::STATUS_PENDING)
                           ->where('due_date', '<', now());
                     });
    }

    public function scopeDueForPayment($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_OVERDUE]);
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$random}";
    }
}