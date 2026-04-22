<?php

namespace App\Providers;

use App\Services\D1DatabaseService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(D1DatabaseService::class, function () {
            return new D1DatabaseService();
        });
    }

    public function boot(): void
    {
        //
    }
}