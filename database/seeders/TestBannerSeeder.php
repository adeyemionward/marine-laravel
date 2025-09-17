<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;
use App\Models\User;

class TestBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a user to assign as banner creator
        $user = User::first();
        
        if (!$user) {
            $this->command->info('No users found. Creating a test user for banners...');
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@marine.ng',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        $banners = [
            // Header Banner
            [
                'title' => 'Special Marine Equipment Sale - Up to 50% Off!',
                'description' => 'Limited time offer on premium marine equipment and accessories',
                'media_type' => 'image',
                'media_url' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800&h=100&fit=crop',
                'link_url' => '/search-results?category=marine-equipment&sale=true',
                'banner_type' => 'header',
                'position' => 'top',
                'status' => 'active',
                'purchase_status' => 'paid',
                'purchaser_id' => $user->id,
                'created_by' => $user->id,
                'purchase_price' => 50000.00,
                'purchased_at' => now(),
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration_days' => 30,
                'priority' => 100,
            ],

            // Hero Banner
            [
                'title' => 'Discover Premium Marine Equipment',
                'description' => 'Connect with verified sellers across Africa for boats, engines, and marine gear',
                'media_type' => 'image',
                'media_url' => 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?w=1200&h=600&fit=crop',
                'link_url' => '/search-results',
                'banner_type' => 'hero',
                'position' => 'middle',
                'status' => 'active',
                'purchase_status' => 'paid',
                'purchaser_id' => $user->id,
                'created_by' => $user->id,
                'purchase_price' => 80000.00,
                'purchased_at' => now(),
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration_days' => 30,
                'priority' => 90,
            ],

            // Sidebar Banner
            [
                'title' => 'Marine Insurance Solutions',
                'description' => 'Protect your investment with comprehensive marine insurance coverage',
                'media_type' => 'image',
                'media_url' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=300&h=250&fit=crop',
                'link_url' => '/marine-insurance',
                'banner_type' => 'sidebar',
                'position' => 'middle',
                'status' => 'active',
                'purchase_status' => 'paid',
                'purchaser_id' => $user->id,
                'created_by' => $user->id,
                'purchase_price' => 30000.00,
                'purchased_at' => now(),
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration_days' => 30,
                'priority' => 80,
            ],

            // Footer Banner
            [
                'title' => 'Join Marine.ng Community - 10,000+ Members',
                'description' => 'Connect with marine enthusiasts and professionals across Africa',
                'media_type' => 'image',
                'media_url' => 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&h=80&fit=crop',
                'link_url' => '/user-registration',
                'banner_type' => 'footer',
                'position' => 'bottom',
                'status' => 'active',
                'purchase_status' => 'paid',
                'purchaser_id' => $user->id,
                'created_by' => $user->id,
                'purchase_price' => 25000.00,
                'purchased_at' => now(),
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'duration_days' => 30,
                'priority' => 70,
            ],
        ];

        foreach ($banners as $bannerData) {
            Banner::create($bannerData);
        }

        $this->command->info('Test banners created successfully!');
    }
}