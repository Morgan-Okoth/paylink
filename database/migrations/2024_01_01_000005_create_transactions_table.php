<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('transaction_type', ['stk_push', 'callback', 'invoice_payment']);
            $table->enum('event', ['initiated', 'success', 'failed', 'cancelled', 'timeout', 'duplicate', 'validation_failed']);
            $table->string('checkout_request_id', 50)->nullable();
            $table->string('merchant_request_id', 50)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('mpesa_receipt_number', 50)->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->integer('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['checkout_request_id']);
            $table->index(['invoice_id', 'event']);
            $table->index(['user_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};