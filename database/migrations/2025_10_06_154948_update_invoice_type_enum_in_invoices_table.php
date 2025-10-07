<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Change column temporarily to VARCHAR (to break ENUM caching)
        DB::statement("
            ALTER TABLE invoices
            CHANGE invoice_type invoice_type VARCHAR(50) NOT NULL DEFAULT 'other';
        ");

        // Step 2: Convert it back to ENUM with new values
        DB::statement("
            ALTER TABLE invoices
            CHANGE invoice_type invoice_type
            ENUM(
                'subscription',
                'commission',
                'penalty',
                'seller_application',
                'banner_purchase',
                'other'
            ) NOT NULL DEFAULT 'other';
        ");
    }

    public function down(): void
    {
        // Reverse the operation
        DB::statement("
            ALTER TABLE invoices
            CHANGE invoice_type invoice_type VARCHAR(50) NOT NULL DEFAULT 'other';
        ");

        DB::statement("
            ALTER TABLE invoices
            CHANGE invoice_type invoice_type
            ENUM(
                'subscription',
                'commission',
                'penalty',
                'seller_application',
                'other'
            ) NOT NULL DEFAULT 'other';
        ");
    }
};
