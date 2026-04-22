<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone_number',
        'address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getActiveInvoices()
    {
        return $this->invoices()->whereIn('status', ['pending', 'overdue'])->get();
    }

    public function getTotalPendingAmount(): float
    {
        return $this->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');
    }

    public function getTotalPaidAmount(): float
    {
        return $this->invoices()
            ->where('status', 'paid')
            ->sum('amount');
    }
}