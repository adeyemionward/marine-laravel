<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove the old role_id column as we're now using Spatie's permission system exclusively.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove old role_id column (deprecated - now using Spatie's model_has_roles table)
            if (Schema::hasColumn('users', 'role_id')) {
                // Try to drop foreign key if it exists
                try {
                    $table->dropForeign(['role_id']);
                } catch (\Exception $e) {
                    // Foreign key may not exist, continue
                }
                $table->dropColumn('role_id');
            }

            // Remove spatie_role_id column as it's redundant (Spatie uses model_has_roles pivot table)
            if (Schema::hasColumn('users', 'spatie_role_id')) {
                // Try to drop foreign key if it exists
                try {
                    $table->dropForeign(['spatie_role_id']);
                } catch (\Exception $e) {
                    // Foreign key may not exist, continue
                }
                $table->dropColumn('spatie_role_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Re-add the columns if needed for rollback
            $table->unsignedBigInteger('role_id')->nullable()->after('email');
            $table->unsignedBigInteger('spatie_role_id')->nullable()->after('role_id');
        });
    }
};
