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
            if (!Schema::hasColumn('user_profiles', 'bio')) {
                $table->text('bio')->nullable()->after('company_description');
            }
            if (!Schema::hasColumn('user_profiles', 'website')) {
                $table->string('website')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('user_profiles', 'linkedin')) {
                $table->string('linkedin')->nullable()->after('website');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['bio', 'website', 'linkedin']);
        });
    }
};
