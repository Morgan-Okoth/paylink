<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('checkout_request_id', 50)->nullable()->unique();
            $table->string('merchant_request_id', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('phone_number', 20);
            $table->enum('status', ['initiated', 'pending', 'completed', 'failed', 'cancelled', 'timeout'])->default('initiated');
            $table->string('mpesa_receipt_number', 50)->nullable();
            $table->string('transaction_date', 30)->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->integer('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['invoice_id', 'status']);
            $table->index('checkout_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};