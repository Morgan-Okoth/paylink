<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class D1Service
{
    private string $accountId;
    private string $databaseId;
    private string $authToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->accountId = $_ENV['CLOUDFLARE_ACCOUNT_ID'] ?? '';
        $this->databaseId = $_ENV['CLOUDFLARE_D1_DATABASE_ID'] ?? 'abfca9a1-5c11-4d87-818f-0a5693841808';
        $this->authToken = $_ENV['CLOUDFLARE_API_TOKEN'] ?? '';
        $this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/d1/database/{$this->databaseId}";
    }

    public function query(string $sql, array $params = []): array
    {
        $cacheKey = 'd1_query_' . md5($sql . json_encode($params));
        
        if (str_starts_with(strtolower(trim($sql)), 'select')) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $response = Http::withToken($this->authToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post($this->baseUrl . '/query', [
                    'sql' => $sql,
                    'params' => $params,
                ]);

            if (!$response->successful()) {
                Log::error('D1 Query Failed', [
                    'sql' => $sql,
                    'params' => $params,
                    'response' => $response->json(),
                ]);
                return ['error' => true, 'message' => $response->body()];
            }

            $result = $response->json();
            
            if (isset($result['result']) && isset($result['result'][0])) {
                $data = $result['result'][0]['results'] ?? [];
                
                if (str_starts_with(strtolower(trim($sql)), 'select')) {
                    Cache::put($cacheKey, $data, 60);
                }
                
                return $data;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('D1 Exception', ['message' => $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function execute(string $sql, array $params = []): bool
    {
        Cache::flush();

        $response = Http::withToken($this->authToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post($this->baseUrl . '/query', [
                'sql' => $sql,
                'params' => $params,
            ]);

        return $response->successful();
    }

    public function lastInsertId(): ?int
    {
        $result = $this->query("SELECT last_insert_rowid() as id");
        return $result[0]['id'] ?? null;
    }

    public function affectedRows(): int
    {
        $result = $this->query("SELECT changes() as count");
        return $result[0]['count'] ?? 0;
    }

    public function beginTransaction(): bool
    {
        return $this->execute('BEGIN TRANSACTION');
    }

    public function commit(): bool
    {
        return $this->execute('COMMIT');
    }

    public function rollback(): bool
    {
        return $this->execute('ROLLBACK');
    }

    public function getTables(): array
    {
        return $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    }

    public function tableExists(string $table): bool
    {
        $result = $this->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name = ?",
            [$table]
        );
        return count($result) > 0;
    }
}