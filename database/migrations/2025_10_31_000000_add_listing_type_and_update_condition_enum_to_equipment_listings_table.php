<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\ListingType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add the listing_type column
        Schema::table('equipment_listings', function (Blueprint $table) {
            $table->enum('listing_type', ListingType::values())
                ->default(ListingType::SALE->value)
                ->after('category_id');
        });

        // Update the condition enum to include new_like and like_new
        // This is database-specific for MySQL
        DB::statement("ALTER TABLE equipment_listings MODIFY COLUMN `condition` ENUM('new', 'new_like', 'like_new', 'excellent', 'good', 'fair', 'poor') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove listing_type column
        Schema::table('equipment_listings', function (Blueprint $table) {
            $table->dropColumn('listing_type');
        });

        // Revert condition enum to original values
        DB::statement("ALTER TABLE equipment_listings MODIFY COLUMN `condition` ENUM('new', 'excellent', 'good', 'fair', 'poor') NOT NULL");
    }
};
