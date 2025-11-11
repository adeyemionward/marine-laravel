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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Add NIN (National Identification Number)
            if (!Schema::hasColumn('user_profiles', 'nin')) {
                $table->string('nin')->nullable()->after('tax_id')->comment('National Identification Number');
            }

            // Add business-specific fields
            if (!Schema::hasColumn('user_profiles', 'business_phone')) {
                $table->string('business_phone')->nullable()->after('phone')->comment('Business phone number');
            }

            if (!Schema::hasColumn('user_profiles', 'business_address')) {
                $table->text('business_address')->nullable()->after('address')->comment('Business address');
            }

            if (!Schema::hasColumn('user_profiles', 'is_business_account')) {
                $table->boolean('is_business_account')->default(false)->after('business_registration')->comment('Whether this is a business account');
            }

            if (!Schema::hasColumn('user_profiles', 'business_type')) {
                $table->string('business_type')->nullable()->after('is_business_account')->comment('Type of business: sole_proprietorship, partnership, limited_liability_company, corporation, cooperative, other');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'nin',
                'business_phone',
                'business_address',
                'is_business_account',
                'business_type'
            ]);
        });
    }
};
