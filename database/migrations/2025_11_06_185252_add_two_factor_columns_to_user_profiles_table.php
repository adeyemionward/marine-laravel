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
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('user_profiles', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
            }
            if (!Schema::hasColumn('user_profiles', 'two_factor_backup_codes')) {
                $table->json('two_factor_backup_codes')->nullable()->after('two_factor_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_enabled', 'two_factor_backup_codes']);
        });
    }
};
