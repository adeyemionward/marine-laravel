<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update all existing users to have active_status = 1
        // This converts any string 'true' values to integer 1
        DB::table('users')->update(['active_status' => 1]);

        // Now change the column type from string to integer
        Schema::table('users', function (Blueprint $table) {
            $table->integer('active_status')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to string if needed (though not recommended)
        Schema::table('users', function (Blueprint $table) {
            $table->string('active_status')->default('true')->change();
        });
    }
};
