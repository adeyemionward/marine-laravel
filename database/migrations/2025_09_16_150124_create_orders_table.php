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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('equipment_listing_id')->constrained('equipment_listings')->onDelete('cascade');
            
            // Order details
            $table->string('status')->default('pending'); // pending, confirmed, processing, shipped, delivered, cancelled, disputed
            $table->decimal('amount', 15, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            
            // Delivery information
            $table->enum('delivery_method', ['pickup', 'shipping', 'courier'])->default('pickup');
            $table->json('delivery_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->datetime('estimated_delivery')->nullable();
            $table->datetime('actual_delivery')->nullable();
            
            // Payment information
            $table->string('payment_status')->default('pending'); // pending, processing, completed, failed, refunded
            $table->string('payment_method')->nullable(); // bank_transfer, card, wallet, etc.
            $table->string('payment_reference')->nullable();
            $table->datetime('payment_due_date')->nullable();
            $table->datetime('paid_at')->nullable();
            
            // Communication
            $table->text('buyer_notes')->nullable();
            $table->text('seller_notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Tracking
            $table->string('tracking_number')->nullable();
            $table->json('status_history')->nullable(); // Store status change history
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_status']);
            $table->index(['order_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
