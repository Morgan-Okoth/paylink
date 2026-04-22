<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}