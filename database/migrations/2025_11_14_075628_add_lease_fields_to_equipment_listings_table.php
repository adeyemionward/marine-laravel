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
        Schema::table('equipment_listings', function (Blueprint $table) {
            // Lease pricing fields
            $table->decimal('lease_price_daily', 10, 2)->nullable()->after('price');
            $table->decimal('lease_price_weekly', 10, 2)->nullable()->after('lease_price_daily');
            $table->decimal('lease_price_monthly', 10, 2)->nullable()->after('lease_price_weekly');

            // Lease terms and conditions
            $table->integer('lease_minimum_period')->nullable()->after('lease_price_monthly')->comment('Minimum lease period in days');
            $table->decimal('lease_security_deposit', 10, 2)->nullable()->after('lease_minimum_period');
            $table->boolean('lease_maintenance_included')->default(false)->after('lease_security_deposit');
            $table->boolean('lease_insurance_required')->default(false)->after('lease_maintenance_included');
            $table->boolean('lease_operator_license_required')->default(false)->after('lease_insurance_required');
            $table->boolean('lease_commercial_use_allowed')->default(false)->after('lease_operator_license_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_listings', function (Blueprint $table) {
            $table->dropColumn([
                'lease_price_daily',
                'lease_price_weekly',
                'lease_price_monthly',
                'lease_minimum_period',
                'lease_security_deposit',
                'lease_maintenance_included',
                'lease_insurance_required',
                'lease_operator_license_required',
                'lease_commercial_use_allowed',
            ]);
        });
    }
};
