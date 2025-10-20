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
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->index('verification_status');
            $table->index('rating');
            $table->index('review_count');
            $table->index('total_listings');
            $table->index('avg_response_minutes');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['review_count']);
            $table->dropIndex(['total_listings']);
            $table->dropIndex(['avg_response_minutes']);
            $table->dropIndex(['is_featured']);
        });
    }
};
