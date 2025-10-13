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
            $table->decimal('credit_limit', 15, 2)->default(0)->after('address')->comment('Credit limit for customer');
            $table->string('customer_type')->nullable()->after('credit_limit')->comment('Type: individual, business, government, nonprofit');
            $table->string('customer_code')->nullable()->unique()->after('customer_type')->comment('Unique customer identifier');
            $table->string('tax_id')->nullable()->after('customer_code')->comment('Tax identification number');
            $table->string('business_registration')->nullable()->after('tax_id')->comment('Business registration number');

            // Supplier-specific fields
            $table->string('supplier_type')->nullable()->after('business_registration')->comment('Type: equipment_supplier, parts_supplier, service_provider, maintenance_provider, consultant, other');
            $table->string('supplier_code')->nullable()->unique()->after('supplier_type')->comment('Unique supplier identifier');
            $table->integer('payment_terms')->default(30)->after('supplier_code')->comment('Payment terms in days');
            $table->boolean('is_preferred')->default(false)->after('payment_terms')->comment('Is preferred supplier');

            // Common fields
            $table->string('city')->nullable()->after('is_preferred');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->default('Nigeria')->after('postal_code');
            $table->text('notes')->nullable()->after('country')->comment('Additional notes');
            $table->enum('status', ['active', 'inactive', 'pending', 'suspended'])->default('active')->after('notes');
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
