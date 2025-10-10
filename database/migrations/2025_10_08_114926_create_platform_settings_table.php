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
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('key');
        });

        // Insert default settings
        DB::table('platform_settings')->insert([
            [
                'key' => 'pricing_featured_listing_category',
                'value' => '30000',
                'type' => 'number',
                'description' => 'Price for category featured listing',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_featured_listing_homepage',
                'value' => '50000',
                'type' => 'number',
                'description' => 'Price for homepage featured listing',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_priority_listing',
                'value' => '30000',
                'type' => 'number',
                'description' => 'Price for priority listing upgrade',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_basic_listing',
                'value' => '0',
                'type' => 'number',
                'description' => 'Price for basic listing',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Priority Listing Levels
            [
                'key' => 'pricing_priority_standard',
                'value' => '5000',
                'type' => 'number',
                'description' => 'Price for standard priority listing',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_priority_high',
                'value' => '10000',
                'type' => 'number',
                'description' => 'Price for high priority listing',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_priority_premium',
                'value' => '20000',
                'type' => 'number',
                'description' => 'Price for premium priority listing',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_tax_rate',
                'value' => '7.5',
                'type' => 'number',
                'description' => 'VAT/Tax rate percentage',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'bank_name',
                'value' => 'First Bank of Nigeria',
                'type' => 'string',
                'description' => 'Bank name for payments',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'account_number',
                'value' => '3052341234',
                'type' => 'string',
                'description' => 'Bank account number',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'account_name',
                'value' => 'MarineNG Limited',
                'type' => 'string',
                'description' => 'Bank account holder name',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Listing Promotions (daily rates)
            [
                'key' => 'pricing_promotion_boost',
                'value' => '3000',
                'type' => 'number',
                'description' => 'Daily rate for listing boost promotion',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_promotion_spotlight',
                'value' => '5000',
                'type' => 'number',
                'description' => 'Daily rate for listing spotlight promotion',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_promotion_super_boost',
                'value' => '8000',
                'type' => 'number',
                'description' => 'Daily rate for listing super boost promotion',
                'created_at' => now(),
                'updated_at' => now()
            ],
            // Verification Badges (one-time fees)
            [
                'key' => 'pricing_verification_business',
                'value' => '25000',
                'type' => 'number',
                'description' => 'One-time fee for business verification badge',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_verification_identity',
                'value' => '10000',
                'type' => 'number',
                'description' => 'One-time fee for identity verification badge',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'pricing_verification_premium',
                'value' => '50000',
                'type' => 'number',
                'description' => 'One-time fee for premium verification badge',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
