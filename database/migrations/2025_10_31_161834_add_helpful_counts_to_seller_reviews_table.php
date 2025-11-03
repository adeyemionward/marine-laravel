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
        Schema::table('seller_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('seller_reviews', 'helpful_count')) {
                $table->integer('helpful_count')->default(0)->after('review');
            }
            if (!Schema::hasColumn('seller_reviews', 'not_helpful_count')) {
                $table->integer('not_helpful_count')->default(0)->after('helpful_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('seller_reviews', 'helpful_count')) {
                $table->dropColumn('helpful_count');
            }
            if (Schema::hasColumn('seller_reviews', 'not_helpful_count')) {
                $table->dropColumn('not_helpful_count');
            }
        });
    }
};
