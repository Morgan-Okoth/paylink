<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schedule;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('db.cloudflare', function ($app) {
            if (isset($_ENV['CLOUDFLARE_D1_DATABASE_ID'])) {
                $databaseId = $_ENV['CLOUDFLARE_D1_DATABASE_ID'];
                $authToken = $_ENV['CLOUDFLARE_API_TOKEN'];
                
                return new class {
                    public function execute(string $sql): array
                    {
                        $accountId = $_ENV['CLOUDFLARE_ACCOUNT_ID'];
                        $databaseId = $_ENV['CLOUDFLARE_D1_DATABASE_ID'];
                        $authToken = $_ENV['CLOUDFLARE_API_TOKEN'];
                        
                        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$accountId}/d1/database/{$databaseId}/query");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: Bearer ' . $authToken,
                            'Content-Type: application/json'
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['sql' => $sql]));
                        
                        $response = curl_exec($ch);
                        curl_close($ch);
                        
                        return json_decode($response, true);
                    }
                };
            }
            return null;
        });
    }

    public function boot(): void
    {
        Schedule::command('invoices:mark-overdue')->dailyAt('00:01');
    }
}