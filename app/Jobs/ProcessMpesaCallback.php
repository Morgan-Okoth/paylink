<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DarajaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProcessMpesaCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private array $callbackData,
        private int $userId,
        private string $checkoutRequestId
    ) {}

    public function handle(DarajaService $darajaService): void
    {
        $user = User::findOrFail($this->userId);
        $daraja = new DarajaService($user);
        
        $parsedCallback = $daraja->processCallback($this->callbackData);
        
        DB::beginTransaction();
        
        try {
            $payment = Payment::where('checkout_request_id', $this->checkoutRequestId)
                ->where('user_id', $this->userId)
                ->first();
                
            if (!$payment) {
                Log::warning('Payment not found for callback', [
                    'checkout_request_id' => $this->checkoutRequestId,
                    'user_id' => $this->userId,
                ]);
                
                Transaction::create([
                    'user_id' => $this->userId,
                    'transaction_type' => Transaction::TYPE_CALLBACK,
                    'event' => Transaction::EVENT_VALIDATION_FAILED,
                    'checkout_request_id' => $this->checkoutRequestId,
                    'result_code' => $parsedCallback['result_code'],
                    'result_desc' => 'Payment record not found',
                    'raw_response' => $this->callbackData,
                    'ip_address' => request()->ip() ?? 'unknown',
                    'processed' => true,
                ]);
                
                DB::commit();
                return;
            }

            if ($payment->status === Payment::STATUS_COMPLETED) {
                Transaction::create([
                    'user_id' => $this->userId,
                    'invoice_id' => $payment->invoice_id,
                    'payment_id' => $payment->id,
                    'transaction_type' => Transaction::TYPE_CALLBACK,
                    'event' => Transaction::EVENT_DUPLICATE,
                    'checkout_request_id' => $this->checkoutRequestId,
                    'merchant_request_id' => $parsedCallback['merchant_request_id'],
                    'phone_number' => $parsedCallback['phone_number'],
                    'amount' => $parsedCallback['amount'],
                    'mpesa_receipt_number' => $parsedCallback['mpesa_receipt_number'],
                    'result_code' => $parsedCallback['result_code'],
                    'result_desc' => 'Duplicate callback - payment already processed',
                    'raw_response' => $this->callbackData,
                    'ip_address' => request()->ip() ?? 'unknown',
                    'processed' => true,
                ]);
                
                DB::commit();
                Log::info('Duplicate callback detected', [
                    'payment_id' => $payment->id,
                    'checkout_request_id' => $this->checkoutRequestId,
                ]);
                return;
            }

            $invoice = $payment->invoice;
            
            if ($parsedCallback['is_successful']) {
                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'mpesa_receipt_number' => $parsedCallback['mpesa_receipt_number'],
                    'transaction_date' => $parsedCallback['transaction_date'],
                    'result_code' => $parsedCallback['result_code'],
                    'result_desc' => $parsedCallback['result_desc'],
                    'callback_received_at' => now(),
                    'processed_at' => now(),
                ]);
                
                $invoice->markAsPaid($parsedCallback['mpesa_receipt_number']);
                
                Transaction::create([
                    'user_id' => $this->userId,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'transaction_type' => Transaction::TYPE_CALLBACK,
                    'event' => Transaction::EVENT_SUCCESS,
                    'checkout_request_id' => $this->checkoutRequestId,
                    'merchant_request_id' => $parsedCallback['merchant_request_id'],
                    'phone_number' => $parsedCallback['phone_number'],
                    'amount' => $parsedCallback['amount'],
                    'mpesa_receipt_number' => $parsedCallback['mpesa_receipt_number'],
                    'result_code' => $parsedCallback['result_code'],
                    'result_desc' => $parsedCallback['result_desc'],
                    'raw_response' => $this->callbackData,
                    'ip_address' => request()->ip() ?? 'unknown',
                    'processed' => true,
                ]);
                
                Log::info('Payment completed successfully', [
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'amount' => $parsedCallback['amount'],
                    'receipt' => $parsedCallback['mpesa_receipt_number'],
                ]);
            } elseif ($parsedCallback['is_cancelled']) {
                $payment->markAsCancelled();
                
                Transaction::create([
                    'user_id' => $this->userId,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'transaction_type' => Transaction::TYPE_CALLBACK,
                    'event' => Transaction::EVENT_CANCELLED,
                    'checkout_request_id' => $this->checkoutRequestId,
                    'result_code' => $parsedCallback['result_code'],
                    'result_desc' => $parsedCallback['result_desc'],
                    'raw_response' => $this->callbackData,
                    'ip_address' => request()->ip() ?? 'unknown',
                    'processed' => true,
                ]);
            } elseif ($parsedCallback['is_timeout']) {
                $payment->markAsTimeout();
                
                Transaction::create([
                    'user_id' => $this->userId,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'transaction_type' => Transaction::TYPE_CALLBACK,
                    'event' => Transaction::EVENT_TIMEOUT,
                    'checkout_request_id' => $this->checkoutRequestId,
                    'result_code' => $parsedCallback['result_code'],
                    'result_desc' => $parsedCallback['result_desc'],
                    'raw_response' => $this->callbackData,
                    'ip_address' => request()->ip() ?? 'unknown',
                    'processed' => true,
                ]);
            } else {
                $payment->markAsFailed(
                    $parsedCallback['result_code'],
                    $parsedCallback['result_desc']
                );
                
                Transaction::create([
                    'user_id' => $this->userId,
                    'invoice_id' => $invoice->id,
                    'payment_id' => $payment->id,
                    'transaction_type' => Transaction::TYPE_CALLBACK,
                    'event' => Transaction::EVENT_FAILED,
                    'checkout_request_id' => $this->checkoutRequestId,
                    'result_code' => $parsedCallback['result_code'],
                    'result_desc' => $parsedCallback['result_desc'],
                    'raw_response' => $this->callbackData,
                    'ip_address' => request()->ip() ?? 'unknown',
                    'processed' => true,
                ]);
            }
            
            DB::commit();
            
        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Callback processing failed', [
                'checkout_request_id' => $this->checkoutRequestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('M-Pesa callback job failed permanently', [
            'checkout_request_id' => $this->checkoutRequestId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}