<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public const TYPE_STK_PUSH = 'stk_push';
    public const TYPE_CALLBACK = 'callback';
    public const TYPE_INVOICE_PAYMENT = 'invoice_payment';

    public const EVENT_INITIATED = 'initiated';
    public const EVENT_SUCCESS = 'success';
    public const EVENT_FAILED = 'failed';
    public const EVENT_CANCELLED = 'cancelled';
    public const EVENT_TIMEOUT = 'timeout';
    public const EVENT_DUPLICATE = 'duplicate';
    public const EVENT_VALIDATION_FAILED = 'validation_failed';

    protected $fillable = [
        'user_id',
        'invoice_id',
        'payment_id',
        'transaction_type',
        'event',
        'checkout_request_id',
        'merchant_request_id',
        'phone_number',
        'amount',
        'mpesa_receipt_number',
        'raw_request',
        'raw_response',
        'result_code',
        'result_desc',
        'ip_address',
        'processed',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'raw_request' => 'array',
        'raw_response' => 'array',
        'processed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isSuccessful(): bool
    {
        return $this->event === self::EVENT_SUCCESS;
    }

    public function isProcessed(): bool
    {
        return $this->processed === true;
    }

    public function markAsProcessed(): void
    {
        $this->update(['processed' => true]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }
}