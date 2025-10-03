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
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', ['income', 'expense']);
            $table->string('description')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default categories
        DB::table('financial_categories')->insert([
            // Income categories
            ['name' => 'subscription_fees', 'type' => 'income', 'description' => 'Subscription fees from users', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'listing_fees', 'type' => 'income', 'description' => 'Equipment listing fees', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'featured_listing_fees', 'type' => 'income', 'description' => 'Featured listing upgrade fees', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'banner_ads', 'type' => 'income', 'description' => 'Banner advertisement revenue', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'commission_fees', 'type' => 'income', 'description' => 'Transaction commission fees', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'other_income', 'type' => 'income', 'description' => 'Other income sources', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],

            // Expense categories
            ['name' => 'server_hosting', 'type' => 'expense', 'description' => 'Server and hosting costs', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'marketing', 'type' => 'expense', 'description' => 'Marketing and advertising expenses', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'payment_processing', 'type' => 'expense', 'description' => 'Payment gateway fees', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'office_expenses', 'type' => 'expense', 'description' => 'Office and administrative expenses', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'staff_salaries', 'type' => 'expense', 'description' => 'Staff salaries and benefits', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'other_expenses', 'type' => 'expense', 'description' => 'Other business expenses', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
