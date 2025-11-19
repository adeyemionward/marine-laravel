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
        // Add 'bottom_left' and 'bottom_right' to the banner_type enum
        DB::statement("ALTER TABLE banner_pricing MODIFY COLUMN banner_type ENUM('header', 'hero', 'sidebar', 'footer',
        'bottom_middle', 'bottom_left', 'bottom_right', 'sidebar_left', 'sidebar_right', 'middle' ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'bottom_left' and 'bottom_right' from enum
        // Note: This will fail if there are existing records with these types
        DB::statement("ALTER TABLE banner_pricing MODIFY COLUMN banner_type ENUM('header', 'hero', 'sidebar', 'footer', 'bottom_middle') NOT NULL");
    }
};
