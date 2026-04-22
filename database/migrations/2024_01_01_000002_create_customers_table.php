<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone_number', 20);
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'email']);
            $table->index(['user_id', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};