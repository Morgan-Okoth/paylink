<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMpesaCallback;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MpesaCallbackController extends Controller
{
    public function handleCallback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'checkout_request_id' => 'required|string',
            'merchant_request_id' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            Log::warning('Invalid callback format', [
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Invalid callback format',
            ]);
        }
        
        $checkoutRequestId = $request->input('checkout_request_id');
        $merchantRequestId = $request->input('merchant_request_id');
        
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)
            ->first();
        
        if (!$payment) {
            Log::warning('Callback for unknown payment', [
                'checkout_request_id' => $checkoutRequestId,
                'ip' => $request->ip(),
            ]);
            
            Transaction::create([
                'user_id' => 0,
                'transaction_type' => Transaction::TYPE_CALLBACK,
                'event' => Transaction::EVENT_VALIDATION_FAILED,
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId,
                'result_desc' => 'Payment record not found',
                'raw_response' => $request->all(),
                'ip_address' => $request->ip(),
                'processed' => true,
            ]);
            
            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted',
            ]);
        }
        
        ProcessMpesaCallback::dispatch(
            $request->all(),
            $payment->user_id,
            $checkoutRequestId
        )->onQueue('mpesa-callbacks');
        
        Log::info('Callback queued for processing', [
            'checkout_request_id' => $checkoutRequestId,
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
        ]);
        
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    public function handleValidation(Request $request): JsonResponse
    {
        $checkoutRequestId = $request->input('CheckoutRequestID');
        $merchantRequestId = $request->input('MerchantRequestID');
        
        $amount = $request->input('Amount');
        $phoneNumber = $request->input('PhoneNumber');
        
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)
            ->first();
        
        if (!$payment) {
            Log::warning('Validation request for unknown payment', [
                'checkout_request_id' => $checkoutRequestId,
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Invalid transaction',
            ]);
        }
        
        if ((float) $amount !== (float) $payment->amount) {
            Log::warning('Amount mismatch in validation', [
                'expected' => $payment->amount,
                'received' => $amount,
                'checkout_request_id' => $checkoutRequestId,
            ]);
            
            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Amount mismatch',
            ]);
        }
        
        Log::info('Transaction validation passed', [
            'checkout_request_id' => $checkoutRequestId,
            'amount' => $amount,
        ]);
        
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Validation passed',
        ]);
    }

    public function handleTimeout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'checkout_request_id' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }
        
        $checkoutRequestId = $request->input('checkout_request_id');
        
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
            ->first();
        
        if ($payment) {
            $payment->markAsTimeout();
            
            Transaction::create([
                'user_id' => $payment->user_id,
                'invoice_id' => $payment->invoice_id,
                'payment_id' => $payment->id,
                'transaction_type' => Transaction::TYPE_STK_PUSH,
                'event' => Transaction::EVENT_TIMEOUT,
                'checkout_request_id' => $checkoutRequestId,
                'result_desc' => 'STK Push timed out - no response from customer',
                'processed' => true,
            ]);
            
            Log::info('Payment marked as timeout', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
            ]);
        }
        
        return response()->json(['status' => 'ok']);
    }
}