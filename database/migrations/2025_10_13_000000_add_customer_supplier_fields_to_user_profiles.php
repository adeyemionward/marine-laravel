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
            // Customer-specific fields
            if (!Schema::hasColumn('user_profiles', 'credit_limit')) {
                $table->decimal('credit_limit', 15, 2)->default(0)->after('address')->comment('Credit limit for customer');
            }
            if (!Schema::hasColumn('user_profiles', 'customer_type')) {
                $table->string('customer_type')->nullable()->after('credit_limit')->comment('Type: individual, business, government, nonprofit');
            }
            if (!Schema::hasColumn('user_profiles', 'customer_code')) {
                $table->string('customer_code')->nullable()->unique()->after('customer_type')->comment('Unique customer identifier');
            }
            if (!Schema::hasColumn('user_profiles', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('customer_code')->comment('Tax identification number');
            }
            if (!Schema::hasColumn('user_profiles', 'business_registration')) {
                $table->string('business_registration')->nullable()->after('tax_id')->comment('Business registration number');
            }

            // Supplier-specific fields
            if (!Schema::hasColumn('user_profiles', 'supplier_type')) {
                $table->string('supplier_type')->nullable()->after('business_registration')->comment('Type: equipment_supplier, parts_supplier, service_provider, maintenance_provider, consultant, other');
            }
            if (!Schema::hasColumn('user_profiles', 'supplier_code')) {
                $table->string('supplier_code')->nullable()->unique()->after('supplier_type')->comment('Unique supplier identifier');
            }
            if (!Schema::hasColumn('user_profiles', 'payment_terms')) {
                $table->integer('payment_terms')->default(30)->after('supplier_code')->comment('Payment terms in days');
            }
            if (!Schema::hasColumn('user_profiles', 'is_preferred')) {
                $table->boolean('is_preferred')->default(false)->after('payment_terms')->comment('Is preferred supplier');
            }

            // Common fields
            if (!Schema::hasColumn('user_profiles', 'city')) {
                $table->string('city')->nullable()->after('is_preferred');
            }
            if (!Schema::hasColumn('user_profiles', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('user_profiles', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('state');
            }
            if (!Schema::hasColumn('user_profiles', 'country')) {
                $table->string('country')->default('Nigeria')->after('postal_code');
            }
            if (!Schema::hasColumn('user_profiles', 'notes')) {
                $table->text('notes')->nullable()->after('country')->comment('Additional notes');
            }
            if (!Schema::hasColumn('user_profiles', 'status')) {
                $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('active')->after('notes');
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
                'credit_limit',
                'customer_type',
                'customer_code',
                'tax_id',
                'business_registration',
                'supplier_type',
                'supplier_code',
                'payment_terms',
                'is_preferred',
                'city',
                'state',
                'postal_code',
                'country',
                'notes',
                'status'
            ]);
        });
    }
};
