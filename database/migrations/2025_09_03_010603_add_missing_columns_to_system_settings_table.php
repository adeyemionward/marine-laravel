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
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('key')->unique()->after('id');
            $table->text('value')->nullable()->after('key');
            $table->string('type')->default('string')->after('value');
            $table->text('description')->nullable()->after('type');
            $table->boolean('is_public')->default(false)->after('description');
            
            // Add index for performance
            $table->index('is_public');
        });
        
        // Insert default system settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['key', 'value', 'type', 'description', 'is_public']);
        });
    }
    
    /**
     * Seed default system settings
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'Marine.ng',
                'type' => 'string',
                'description' => 'The name of the website',
                'is_public' => true,
            ],
            [
                'key' => 'site_description',
                'value' => 'Africa\'s Premier Marine Equipment Marketplace',
                'type' => 'string',
                'description' => 'Site description for SEO',
                'is_public' => true,
            ],
            [
                'key' => 'contact_email',
                'value' => 'info@marine.ng',
                'type' => 'string',
                'description' => 'Main contact email',
                'is_public' => true,
            ],
            [
                'key' => 'contact_phone',
                'value' => '+234-800-MARINE',
                'type' => 'string',
                'description' => 'Main contact phone',
                'is_public' => true,
            ],
            [
                'key' => 'social_facebook',
                'value' => 'https://facebook.com/marineng',
                'type' => 'string',
                'description' => 'Facebook page URL',
                'is_public' => true,
            ],
            [
                'key' => 'social_twitter',
                'value' => 'https://twitter.com/marineng',
                'type' => 'string',
                'description' => 'Twitter profile URL',
                'is_public' => true,
            ],
            [
                'key' => 'social_instagram',
                'value' => 'https://instagram.com/marineng',
                'type' => 'string',
                'description' => 'Instagram profile URL',
                'is_public' => true,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable/disable maintenance mode',
                'is_public' => true,
            ],
            [
                'key' => 'items_per_page',
                'value' => '20',
                'type' => 'integer',
                'description' => 'Default items per page',
                'is_public' => false,
            ],
            [
                'key' => 'max_upload_size',
                'value' => '10485760',
                'type' => 'integer',
                'description' => 'Maximum upload size in bytes (10MB)',
                'is_public' => false,
            ],
            [
                'key' => 'currency',
                'value' => 'NGN',
                'type' => 'string',
                'description' => 'Default currency',
                'is_public' => true,
            ],
            [
                'key' => 'timezone',
                'value' => 'Africa/Lagos',
                'type' => 'string',
                'description' => 'Default timezone',
                'is_public' => true,
            ],
        ];
        
        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
};
