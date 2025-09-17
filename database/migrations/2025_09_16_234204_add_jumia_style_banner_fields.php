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
            // Banner dimensions and responsive settings
            $table->string('banner_size')->after('position')->default('large'); // small, medium, large, full_width
            $table->json('dimensions')->nullable()->after('banner_size'); // {width: 1920, height: 400}
            $table->json('mobile_dimensions')->nullable()->after('dimensions'); // {width: 375, height: 200}

            // Banner positioning and display
            $table->string('display_context')->after('mobile_dimensions')->default('homepage'); // homepage, category, listing_detail, search
            $table->integer('sort_order')->after('display_context')->default(0);
            $table->boolean('show_on_mobile')->after('sort_order')->default(true);
            $table->boolean('show_on_desktop')->after('show_on_mobile')->default(true);

            // Category and targeting
            $table->unsignedBigInteger('target_category_id')->nullable()->after('show_on_desktop');
            $table->json('target_locations')->nullable()->after('target_category_id'); // ['Lagos', 'Abuja']
            $table->enum('user_target', ['all', 'logged_in', 'sellers', 'buyers'])->after('target_locations')->default('all');

            // Content and styling
            $table->string('background_color')->nullable()->after('user_target');
            $table->string('text_color')->nullable()->after('background_color');
            $table->string('button_text')->nullable()->after('text_color');
            $table->string('button_color')->nullable()->after('button_text');
            $table->json('overlay_settings')->nullable()->after('button_color'); // {enabled: true, opacity: 0.5, color: '#000'}

            // Performance and analytics
            $table->decimal('conversion_rate', 5, 2)->after('overlay_settings')->default(0);
            $table->integer('max_impressions')->nullable()->after('conversion_rate');
            $table->integer('max_clicks')->nullable()->after('max_impressions');

            // Add foreign key for category targeting
            $table->foreign('target_category_id')->references('id')->on('equipment_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['target_category_id']);
            $table->dropColumn([
                'banner_size',
                'dimensions',
                'mobile_dimensions',
                'display_context',
                'sort_order',
                'show_on_mobile',
                'show_on_desktop',
                'target_category_id',
                'target_locations',
                'user_target',
                'background_color',
                'text_color',
                'button_text',
                'button_color',
                'overlay_settings',
                'conversion_rate',
                'max_impressions',
                'max_clicks'
            ]);
        });
    }
};
