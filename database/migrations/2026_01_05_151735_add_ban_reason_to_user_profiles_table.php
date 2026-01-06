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
            if (!Schema::hasColumn('user_profiles', 'ban_reason')) {
                $table->string('ban_reason')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('user_profiles', 'banned_until')) {
                $table->timestamp('banned_until')->nullable()->after('ban_reason');
            }
            if (!Schema::hasColumn('user_profiles', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('user_profiles', 'ban_reason')) {
                $table->dropColumn('ban_reason');
            }
            if (Schema::hasColumn('user_profiles', 'banned_until')) {
                $table->dropColumn('banned_until');
            }
            if (Schema::hasColumn('user_profiles', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
        });
    }
};