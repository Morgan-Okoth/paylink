<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('business_name');
            $table->string('phone_number', 20);
            $table->string('mpesa_shortcode', 10);
            $table->string('mpesa_consumer_key', 100);
            $table->string('mpesa_consumer_secret', 200);
            $table->string('mpesa_passkey', 200);
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};