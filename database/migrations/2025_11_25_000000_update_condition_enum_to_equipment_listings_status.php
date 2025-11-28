<?php

use App\Enums\ListingStatus;
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
      

        // Update the condition enum to include new_like and like_new
        // This is database-specific for MySQL
        DB::statement("ALTER TABLE equipment_listings MODIFY COLUMN `status` ENUM('sold', 'hired', 'active', 'draft', 'pending', 'archived', 'rejected') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        

        // Revert condition enum to original values
        DB::statement("ALTER TABLE equipment_listings MODIFY COLUMN `status` ENUM('active','rejected') NOT NULL");

    }
};
