<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'business_name',
        'phone_number',
        'mpesa_shortcode',
        'mpesa_consumer_key',
        'mpesa_consumer_secret',
        'mpesa_passkey',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mpesa_consumer_secret',
        'mpesa_passkey',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getMpesaCredentials(): array
    {
        return [
            'shortcode' => $this->mpesa_shortcode,
            'consumer_key' => $this->mpesa_consumer_key,
            'consumer_secret' => $this->mpesa_consumer_secret,
            'passkey' => $this->mpesa_passkey,
        ];
    }
}