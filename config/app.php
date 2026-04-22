<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'name' => env('APP_NAME', 'PayLink'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'Africa/Nairobi',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
    ])->toArray(),

    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'database'),
        'connections' => [
            'sync' => [
                'driver' => 'sync',
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
                'after_commit' => false,
            ],
            'mpesa-callbacks' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'mpesa-callbacks',
                'retry_after' => 90,
                'after_commit' => false,
            ],
        ],
    ],

    'session' => [
        'driver' => env('SESSION_DRIVER', 'database'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => storage_path('framework/sessions'),
        'connection' => env('SESSION_CONNECTION'),
        'table' => 'sessions',
        'store' => env('SESSION_STORE'),
        'lottery' => [2, 100],
        'cookie' => env('SESSION_COOKIE', 'paylink_session'),
        'path' => '/',
        'domain' => env('SESSION_DOMAIN'),
        'secure' => env('SESSION_SECURE_COOKIE'),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    'cache' => [
        'default' => env('CACHE_DRIVER', 'database'),
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => storage_path('framework/cache/data'),
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'cache',
                'connection' => null,
                'lock_connection' => null,
            ],
        ],
        'prefix' => env('CACHE_PREFIX', 'paylink_cache_'),
    ],

    'logging' => [
        'default' => env('LOG_CHANNEL', 'stack'),
        'deprecations' => [
            'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
            'trace' => false,
        ],
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single'],
                'ignore_exceptions' => false,
            ],
            'single' => [
                'driver' => 'single',
                'path' => storage_path('logs/laravel.log'),
                'level' => env('LOG_LEVEL', 'debug'),
            ],
        ],
    ],

];