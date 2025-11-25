<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use App\Models\EquipmentCategory;
use App\Models\EquipmentListing;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\SellerProfile;
use App\Models\UserFavorite;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Create roles if they don't exist
        $userRole = Role::firstOrCreate(['name' => 'user'], [
            'display_name' => 'User',
            'description' => 'Regular user',
            'permissions' => ['view_listings', 'create_inquiries']
        ]);

        $sellerRole = Role::firstOrCreate(['name' => 'seller'], [
            'display_name' => 'Seller',
            'description' => 'Verified seller',
            'permissions' => ['view_listings', 'create_inquiries', 'create_listings', 'manage_listings']
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'Administrator',
            'description' => 'System administrator',
            'permissions' => ['*']
        ]);

        // Create subscription plans
        $freePlan = SubscriptionPlan::firstOrCreate([
            'name' => 'Free',
            'tier' => 'freemium'
        ], [
            'description' => 'Basic listing features',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'features' => ['Basic listings', 'Standard support'],
            'limits' => ['max_listings' => 3, 'max_images_per_listing' => 3],
            'max_listings' => 3,
            'max_images_per_listing' => 3,
            'priority_support' => false,
            'analytics_access' => false,
            'custom_branding' => false,
            'is_active' => true,
            'sort_order' => 1
        ]);

        $proPlan = SubscriptionPlan::firstOrCreate([
            'name' => 'Professional',
            'tier' => 'premium'
        ], [
            'description' => 'Advanced features for professionals',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'features' => ['Unlimited listings', 'Priority support', 'Analytics'],
            'limits' => ['max_listings' => -1, 'max_images_per_listing' => 10],
            'max_listings' => -1,
            'max_images_per_listing' => 10,
            'priority_support' => true,
            'analytics_access' => true,
            'custom_branding' => true,
            'is_active' => true,
            'sort_order' => 2
        ]);

        // Create equipment categories
        $categories = [
            'Boats' => 'All types of boats and vessels',
            'Engines' => 'Marine engines and motors',
            'Electronics' => 'Marine electronics and navigation',
            'Safety Equipment' => 'Safety gear and equipment',
            'Parts & Accessories' => 'Marine parts and accessories'
        ];

        foreach ($categories as $name => $description) {
            $slug = \Illuminate\Support\Str::slug($name);
            EquipmentCategory::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'is_active' => true,
                'sort_order' => 1
            ]);
        }

        // Create test users
        $testUser = User::firstOrCreate(['email' => 'user@marine.africa'], [
            'name' => 'Test User',
            'email' => 'user@marine.africa',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
            'email_verified_at' => now()
        ]);

        $testSeller = User::firstOrCreate(['email' => 'seller@marine.africa'], [
            'name' => 'Test Seller',
            'email' => 'seller@marine.africa',
            'password' => Hash::make('password'),
            'role_id' => $sellerRole->id,
            'email_verified_at' => now()
        ]);

        $testAdmin = User::firstOrCreate(['email' => 'admin@marine.africa'], [
            'name' => 'Admin User',
            'email' => 'admin@marine.africa',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'email_verified_at' => now()
        ]);

        // Create user profiles
        UserProfile::firstOrCreate(['user_id' => $testUser->id], [
            'full_name' => 'Test User',
            'phone' => '+234-801-234-5678',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria'
        ]);

        UserProfile::firstOrCreate(['user_id' => $testSeller->id], [
            'full_name' => 'Test Seller',
            'phone' => '+234-801-234-5679',
            'city' => 'Abuja',
            'state' => 'FCT',
            'country' => 'Nigeria'
        ]);

        UserProfile::firstOrCreate(['user_id' => $testAdmin->id], [
            'full_name' => 'Admin User',
            'phone' => '+234-801-234-5680',
            'city' => 'Port Harcourt',
            'state' => 'Rivers',
            'country' => 'Nigeria'
        ]);

        // Create seller profile for test seller
        SellerProfile::firstOrCreate(['user_id' => $testSeller->id], [
            'business_name' => 'Marine Equipment Pro',
            'business_description' => 'Professional marine equipment supplier with over 10 years experience',
            'specialties' => ['Boats', 'Engines', 'Electronics'],
            'years_active' => 5,
            'rating' => 4.7,
            'review_count' => 23,
            'total_listings' => 12,
            'response_time' => '< 2 hours',
            'avg_response_minutes' => 90,
            'verification_status' => 'approved',
            'verified_at' => now(),
            'is_featured' => true,
            'featured_priority' => 1
        ]);

        // Create subscriptions
        Subscription::firstOrCreate([
            'user_id' => $testUser->id,
            'status' => 'active'
        ], [
            'plan_id' => $freePlan->id,
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'auto_renew' => true
        ]);

        Subscription::firstOrCreate([
            'user_id' => $testSeller->id,
            'status' => 'active'
        ], [
            'plan_id' => $proPlan->id,
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
            'auto_renew' => true
        ]);

        // Create some sample listings
        $boatCategory = EquipmentCategory::where('name', 'Boats')->first();
        $engineCategory = EquipmentCategory::where('name', 'Engines')->first();

        $listings = [
            [
                'seller_id' => $testSeller->id,
                'category_id' => $boatCategory->id,
                'title' => 'Speed Boat - 25ft Fiberglass',
                'description' => 'Excellent condition speed boat, perfect for fishing and leisure. Well maintained with recent engine service.',
                'brand' => 'Yamaha',
                'model' => 'SR250',
                'year' => 2020,
                'condition' => 'good',
                'price' => 2500000,
                'location_state' => 'Lagos',
                'location_city' => 'Victoria Island',
                'status' => 'active',
                'published_at' => now(),
                'view_count' => 156,
                'specifications' => ['Length: 25ft', 'Engine: 150HP', 'Capacity: 8 people'],
                'contact_phone' => '+234-801-234-5679',
                'contact_email' => 'seller@marine.africa',
                'is_price_negotiable' => true
            ],
            [
                'seller_id' => $testSeller->id,
                'category_id' => $engineCategory->id,
                'title' => 'Outboard Motor - 75HP Mercury',
                'description' => 'Reliable outboard motor in excellent working condition. Recently serviced with all maintenance records.',
                'brand' => 'Mercury',
                'model' => '75HP FourStroke',
                'year' => 2019,
                'condition' => 'excellent',
                'price' => 850000,
                'location_state' => 'Lagos',
                'location_city' => 'Ikoyi',
                'status' => 'active',
                'published_at' => now(),
                'view_count' => 89,
                'specifications' => ['Power: 75HP', 'Type: 4-Stroke', 'Weight: 165kg'],
                'contact_phone' => '+234-801-234-5679',
                'contact_email' => 'seller@marine.africa',
                'is_price_negotiable' => false
            ]
        ];

        foreach ($listings as $listingData) {
            EquipmentListing::firstOrCreate([
                'title' => $listingData['title'],
                'seller_id' => $listingData['seller_id']
            ], $listingData);
        }

        $this->command->info('Test data seeded successfully!');
        $this->command->info('Test users created:');
        $this->command->info('- User: user@marine.africa / password');
        $this->command->info('- Seller: seller@marine.africa / password');
        $this->command->info('- Admin: admin@marine.africa / password');
    }
}