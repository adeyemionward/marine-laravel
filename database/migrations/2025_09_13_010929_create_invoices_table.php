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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_application_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->enum('invoice_type', ['subscription', 'commission', 'penalty', 'seller_application', 'other'])->default('other');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->json('items')->nullable(); // Store invoice line items as JSON
            $table->string('company_name')->nullable();
            $table->string('generated_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['status', 'due_date']);
            $table->index('invoice_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
