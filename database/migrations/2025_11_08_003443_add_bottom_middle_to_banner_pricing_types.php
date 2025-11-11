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
        // Add bottom banner positions to the banner_type enum in banner_pricing table
        DB::statement("ALTER TABLE banner_pricing MODIFY COLUMN banner_type ENUM('header', 'hero', 'sidebar', 'footer', 'bottom_left', 'bottom_middle', 'bottom_right') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove bottom banner positions from the banner_type enum
        // Note: This will fail if there are existing records with these types
        DB::statement("ALTER TABLE banner_pricing MODIFY COLUMN banner_type ENUM('header', 'hero', 'sidebar', 'footer') NOT NULL");
    }
};
