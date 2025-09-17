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
        Schema::table('banners', function (Blueprint $table) {
            // Update banner_type column to allow new values
            $table->string('banner_type')->change();

            // Update banner_size column to allow new values
            $table->string('banner_size')->change();

            // Update display_context column to allow new values
            $table->string('display_context')->change();

            // Update user_target column to allow new values
            $table->string('user_target')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Revert changes if needed
            $table->enum('banner_type', ['promotional', 'sponsored'])->change();
            $table->enum('banner_size', ['small', 'medium', 'large'])->change();
            $table->enum('display_context', ['homepage', 'category'])->change();
            $table->enum('user_target', ['all', 'logged_in'])->change();
        });
    }
};