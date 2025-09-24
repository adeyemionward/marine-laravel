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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique();
            $table->enum('transaction_type', ['income', 'expense']);
            $table->enum('category', [
                // Income categories
                'subscription_revenue',
                'featured_listing_revenue',
                'priority_listing_revenue',
                'banner_ad_revenue',
                'commission_revenue',
                'equipment_sales',
                'equipment_leasing',
                'maintenance_services',
                'installation_services',
                'consultation_services',
                'training_services',
                'spare_parts_sales',
                'warranty_extensions',
                'inspection_services',
                'brokerage_commissions',
                'other_income',
                // Expense categories
                'server_costs',
                'marketing_expenses',
                'maintenance_costs',
                'staff_salaries',
                'equipment_procurement',
                'warehouse_operations',
                'shipping_logistics',
                'insurance_coverage',
                'regulatory_compliance',
                'equipment_testing',
                'technical_support',
                'sales_commissions',
                'trade_show_marketing',
                'professional_services',
                'equipment_depreciation',
                'research_development',
                'other_expense'
            ]);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->text('description');
            $table->text('notes')->nullable();
            $table->timestamp('transaction_date');

            // Related entities
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('equipment_listing_id')->nullable();
            $table->unsignedBigInteger('banner_purchase_id')->nullable();
            $table->string('related_model_type')->nullable(); // polymorphic
            $table->unsignedBigInteger('related_model_id')->nullable(); // polymorphic

            // Payment information
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');

            // Metadata
            $table->json('metadata')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['transaction_type', 'category']);
            $table->index(['transaction_date']);
            $table->index(['user_id', 'transaction_type']);
            $table->index(['payment_status']);
            $table->index(['related_model_type', 'related_model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
