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
        Schema::create('banner_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->integer('value')->default(4000);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('banner_settings')->insert([
            [
                'key' => 'hero_banner_transition_time',
                'value' => 4000,
                'description' => 'Transition time for Hero Banners (in milliseconds)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'banner_display_transition_time',
                'value' => 4000,
                'description' => 'Transition time for Banner Display (in milliseconds)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sidebar_banner_transition_time',
                'value' => 5000,
                'description' => 'Transition time for Sidebar Banners (in milliseconds)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banner_settings');
    }
};
