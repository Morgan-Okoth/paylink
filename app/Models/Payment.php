<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TIMEOUT = 'timeout';

    protected $fillable = [
        'user_id',
        'invoice_id',
        'checkout_request_id',
        'merchant_request_id',
        'amount',
        'phone_number',
        'status',
        'mpesa_receipt_number',
        'transaction_date',
        'callback_received_at',
        'result_code',
        'result_desc',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'callback_received_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'user_id',
        'invoice_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_TIMEOUT]);
    }

    public function markAsCompleted(string $receiptNumber, string $transactionDate): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'mpesa_receipt_number' => $receiptNumber,
            'transaction_date' => $transactionDate,
            'processed_at' => now(),
        ]);

        return true;
    }

    public function markAsFailed(int $resultCode, string $resultDesc): bool
    {
        if ($this->isSuccessful()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'processed_at' => now(),
        ]);

        return true;
    }

    public function markAsTimeout(): bool
    {
        if ($this->isSuccessful()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_TIMEOUT,
            'result_code' => -1,
            'result_desc' => 'Request timeout - customer did not complete payment',
            'processed_at' => now(),
        ]);

        return true;
    }

    public function markAsCancelled(): bool
    {
        if ($this->isSuccessful()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'result_code' => -1,
            'result_desc' => 'Customer cancelled the transaction',
            'processed_at' => now(),
        ]);

        return true;
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_TIMEOUT]);
    }
}