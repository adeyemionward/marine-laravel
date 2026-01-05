<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_listings', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment_listings', 'seller_profile_id')) {
                $table->foreignId('seller_profile_id')->nullable()->after('seller_id')->constrained('seller_profiles')->nullOnDelete();
            }
        });

        // Backfill existing data using a single optimized query
        // Logic: equipment_listings.seller_id IS user_profiles.id
        // user_profiles.user_id IS seller_profiles.user_id
        // We want equipment_listings.seller_profile_id = seller_profiles.id
        try {
            DB::statement("
                UPDATE equipment_listings el
                INNER JOIN user_profiles up ON el.seller_id = up.id
                INNER JOIN seller_profiles sp ON up.user_id = sp.user_id
                SET el.seller_profile_id = sp.id
                WHERE el.seller_profile_id IS NULL
            ");
        } catch (\Exception $e) {
            // Fallback or ignore if tables are empty/migrating from scratch
            \Log::warning("Could not backfill seller_profile_id: " . $e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::table('equipment_listings', function (Blueprint $table) {
            if (Schema::hasColumn('equipment_listings', 'seller_profile_id')) {
                $table->dropForeign(['seller_profile_id']);
                $table->dropColumn('seller_profile_id');
            }
        });
    }
};
