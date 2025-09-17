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
            // Add priority column for listing ordering
            $table->integer('priority')->default(0)->after('is_featured');
            
            // Add featured_until column for time-based featured status
            $table->timestamp('featured_until')->nullable()->after('priority');
            
            // Add indexes for performance
            $table->index(['priority', 'status']);
            $table->index(['is_featured', 'featured_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_listings', function (Blueprint $table) {
            $table->dropIndex(['priority', 'status']);
            $table->dropIndex(['is_featured', 'featured_until']);
            $table->dropColumn(['priority', 'featured_until']);
        });
    }
};
