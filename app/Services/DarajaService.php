<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DarajaService
{
    private const BASE_URL_SANDBOX = 'https://sandbox.safaricom.co.ke';
    private const BASE_URL_PRODUCTION = 'https://api.safaricom.co.ke';
    private const TOKEN_CACHE_KEY = 'mpesa_access_token';
    private const TOKEN_EXPIRY_SECONDS = 3500;

    private string $baseUrl;
    private string $shortcode;
    private string $consumerKey;
    private string $consumerSecret;
    private string $passkey;
    private int $userId;

    public function __construct(User $user)
    {
        $this->shortcode = $user->mpesa_shortcode;
        $this->consumerKey = $user->mpesa_consumer_key;
        $this->consumerSecret = $user->mpesa_consumer_secret;
        $this->passkey = $user->mpesa_passkey;
        $this->userId = $user->id;
        
        $this->baseUrl = config('app.env') === 'production' 
            ? self::BASE_URL_PRODUCTION 
            : self::BASE_URL_SANDBOX;
    }

    public function initiateStkPush(array $params): array
    {
        $this->validateStkParams($params);

        $token = $this->getAccessToken();
        
        $timestamp = now()->format('YmdHis');
        $password = $this->generatePassword($timestamp);
        
        $request = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $params['amount'],
            'PartyA' => $this->formatPhoneNumber($params['phone_number']),
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $this->formatPhoneNumber($params['phone_number']),
            'CallBackURL' => $params['callback_url'],
            'AccountReference' => $params['account_reference'],
            'TransactionDesc' => $params['transaction_desc'] ?? 'Payment for Invoice',
        ];

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", $request);

        if (!$response->successful()) {
            Log::error('STK Push request failed', [
                'user_id' => $this->userId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            throw new \RuntimeException('STK Push request failed: ' . $response->status());
        }

        $responseData = $response->json();
        
        Log::info('STK Push initiated', [
            'user_id' => $this->userId,
            'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
            'checkout_request_id' => $responseData['CheckoutRequestID'] ?? null,
            'response_code' => $responseData['ResponseCode'] ?? null,
        ]);

        return [
            'success' => ($responseData['ResponseCode'] ?? '1') === '0',
            'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
            'checkout_request_id' => $responseData['CheckoutRequestID'] ?? null,
            'response_code' => $responseData['ResponseCode'] ?? null,
            'response_description' => $responseData['ResponseDescription'] ?? null,
            'customer_message' => $responseData['CustomerMessage'] ?? null,
        ];
    }

    public function queryTransactionStatus(string $checkoutRequestId): array
    {
        $token = $this->getAccessToken();
        
        $timestamp = now()->format('YmdHis');
        $password = $this->generatePassword($timestamp);

        $request = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(30)
            ->post("{$this->baseUrl}/mpesa/stkpushquery/v1/query", $request);

        if (!$response->successful()) {
            throw new \RuntimeException('Transaction status query failed: ' . $response->status());
        }

        return $response->json();
    }

    public function validateCallbackSignature(array $callbackData, string $signature): bool
    {
        $password = base64_decode($signature);
        
        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($callbackData),
            $this->passkey
        );

        return hash_equals($expectedSignature, $password);
    }

    public function processCallback(array $rawCallback): array
    {
        $stkCallback = $rawCallback['stkCallback'] ?? $rawCallback;
        
        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $merchantRequestId = $stkCallback['MerchantRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? -1;
        $resultDesc = $stkCallback['ResultDesc'] ?? '';

        $callbackParams = $stkCallback['CallbackMetadata'] ?? [];
        
        $amount = null;
        $mpesaReceiptNumber = null;
        $transactionDate = null;
        $phoneNumber = null;

        foreach ($callbackParams as $item) {
            $name = $item['Name'] ?? '';
            $value = $item['Value'] ?? null;
            
            switch ($name) {
                case 'Amount':
                    $amount = $value;
                    break;
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $value;
                    break;
                case 'TransactionDate':
                    $transactionDate = is_numeric($value) 
                        ? date('Y-m-d H:i:s', strtotime($value))
                        : $value;
                    break;
                case 'PhoneNumber':
                    $phoneNumber = $value;
                    break;
            }
        }

        return [
            'checkout_request_id' => $checkoutRequestId,
            'merchant_request_id' => $merchantRequestId,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'amount' => $amount,
            'mpesa_receipt_number' => $mpesaReceiptNumber,
            'transaction_date' => $transactionDate,
            'phone_number' => $phoneNumber,
            'is_successful' => $resultCode === 0,
            'is_cancelled' => $resultCode === 1031,
            'is_timeout' => $resultCode === -1 || $resultCode === 2001,
            'raw_callback' => $rawCallback,
        ];
    }

    private function getAccessToken(): string
    {
        $cacheKey = self::TOKEN_CACHE_KEY . "_{$this->userId}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $credentials = base64_encode(
            "{$this->consumerKey}:{$this->consumerSecret}"
        );

        $response = Http::withHeaders([
            'Authorization' => "Basic {$credentials}",
            'Content-Type' => 'application/json',
        ])
        ->timeout(30)
        ->post("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

        if (!$response->successful()) {
            Log::error('Failed to get M-Pesa access token', [
                'user_id' => $this->userId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            throw new \RuntimeException('Failed to get M-Pesa access token');
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;

        if (!$token) {
            throw new \RuntimeException('Invalid access token response');
        }

        $expiresIn = $data['expires_in'] ?? self::TOKEN_EXPIRY_SECONDS;
        Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 300));

        return $token;
    }

    private function generatePassword(string $timestamp): string
    {
        return base64_encode(
            "{$this->shortcode}{$this->passkey}{$timestamp}"
        );
    }

    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }
        
        return $phone;
    }

    private function validateStkParams(array $params): void
    {
        $required = ['amount', 'phone_number', 'callback_url', 'account_reference'];
        
        foreach ($required as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                throw new InvalidArgumentException("Missing required parameter: {$field}");
            }
        }

        if (!is_numeric($params['amount']) || $params['amount'] < 1) {
            throw new InvalidArgumentException('Amount must be a positive number');
        }

        $phone = preg_replace('/[^0-9]/', '', $params['phone_number']);
        if (strlen($phone) < 9 || strlen($phone) > 12) {
            throw new InvalidArgumentException('Invalid phone number format');
        }
    }
}