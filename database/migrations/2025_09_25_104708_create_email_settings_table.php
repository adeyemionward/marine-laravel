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
        Schema::table('email_settings', function (Blueprint $table) {
            // Only add columns that don't exist in the first migration
            if (!Schema::hasColumn('email_settings', 'additional_config')) {
                $table->json('additional_config')->nullable()->after('is_active'); // for API keys, etc.
            }
            if (!Schema::hasColumn('email_settings', 'last_tested_at')) {
                $table->timestamp('last_tested_at')->nullable()->after('additional_config');
            }
            if (!Schema::hasColumn('email_settings', 'test_passed')) {
                $table->boolean('test_passed')->default(false)->after('last_tested_at');
            }
            if (!Schema::hasColumn('email_settings', 'test_error')) {
                $table->text('test_error')->nullable()->after('test_passed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            if (Schema::hasColumn('email_settings', 'additional_config')) {
                $table->dropColumn('additional_config');
            }
            if (Schema::hasColumn('email_settings', 'last_tested_at')) {
                $table->dropColumn('last_tested_at');
            }
            if (Schema::hasColumn('email_settings', 'test_passed')) {
                $table->dropColumn('test_passed');
            }
            if (Schema::hasColumn('email_settings', 'test_error')) {
                $table->dropColumn('test_error');
            }
        });
    }
};
