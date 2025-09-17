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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference')->unique();
            $table->string('transaction_id')->nullable(); // Gateway transaction ID
            
            // Relationships
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->morphs('payable'); // Can be order, invoice, subscription, banner
            
            // Payment details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, cancelled, refunded
            
            // Gateway information
            $table->string('gateway')->nullable(); // flutterwave, paystack, bank_transfer, etc.
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_response')->nullable();
            
            // Payment method
            $table->string('payment_method')->nullable(); // card, bank_transfer, ussd, mobile_money
            $table->json('payment_details')->nullable(); // Store payment method specific details
            
            // Customer information
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            
            // Fees and charges
            $table->decimal('gateway_fee', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('net_amount', 15, 2); // Amount after fees
            
            // Status tracking
            $table->datetime('initiated_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Refund information
            $table->datetime('refunded_at')->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->text('refund_reason')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['gateway', 'gateway_reference']);
            $table->index(['payment_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
