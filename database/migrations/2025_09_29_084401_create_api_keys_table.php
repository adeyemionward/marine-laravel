<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('service'); // e.g., 'sendgrid', 'stripe', 'paystack', etc.
            $table->text('api_key');
            $table->text('secret_key')->nullable();
            $table->json('config')->nullable(); // additional configuration
            $table->enum('status', ['active', 'inactive', 'testing'])->default('active');
            $table->timestamp('last_tested_at')->nullable();
            $table->json('test_result')->nullable(); // store test response
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['service', 'status']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
