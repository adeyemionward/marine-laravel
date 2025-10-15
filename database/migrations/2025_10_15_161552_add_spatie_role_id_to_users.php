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
        Schema::table('users', function (Blueprint $table) {
            // Add the column
            $table->foreignId('spatie_role_id')
                  ->nullable()
                  ->after('role_id')
                  ->constrained('roles') // reference the roles table
                  ->onUpdate('cascade')
                  ->onDelete('set null'); // if role deleted, set null
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['spatie_role_id']);
            $table->dropColumn('spatie_role_id');
        });
    }
};
