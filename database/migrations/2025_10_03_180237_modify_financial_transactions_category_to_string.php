<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change category column from ENUM to VARCHAR to allow flexible categories
        DB::statement('ALTER TABLE financial_transactions MODIFY COLUMN category VARCHAR(100) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to ENUM (optional - not recommended as data might be lost)
        DB::statement("ALTER TABLE financial_transactions MODIFY COLUMN category ENUM(
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
        ) NOT NULL");
    }
};
