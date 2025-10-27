<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserProfile;
use Spatie\Permission\Models\Role; 

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Get roles
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $sellerRole = Role::where('name', 'seller')->where('guard_name', 'api')->first();
        $userRole = Role::where('name', 'user')->where('guard_name', 'api')->first();

        // Create admin user
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@marine.ng'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@marine.ng',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign role using Spatie's method
        $adminUser->assignRole($adminRole);

        UserProfile::updateOrCreate(
            ['user_id' => $adminUser->id],
            [
                'user_id' => $adminUser->id,
                'full_name' => 'System Administrator',
                'role' => 'admin',
                'is_active' => true,
                'is_verified' => true,
                'company_name' => 'Marine Engineering Nigeria',
                'company_description' => 'Leading marine equipment marketplace in Nigeria',
                'phone' => '+234-800-123-4567',
                'address' => '123 Marina Street',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
            ]
        );

        // Create test user
        $testUser = User::updateOrCreate(
            ['email' => 'user@marine.ng'],
            [
                'name' => 'Test User',
                'email' => 'user@marine.ng',
                'password' => Hash::make('user123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign role using Spatie's method
        $testUser->assignRole($userRole);

        UserProfile::updateOrCreate(
            ['user_id' => $testUser->id],
            [
                'user_id' => $testUser->id,
                'full_name' => 'Test User',
                'role' => 'user',
                'is_active' => true,
                'is_verified' => true,
                'phone' => '+234-800-123-4568',
                'address' => '123 Marina Street',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
            ]
        );

        // Create sample sellers
        $sellers = [
            [
                'name' => 'John Okafor',
                'email' => 'john@oceanmarine.ng',
                'company_name' => 'Ocean Marine Equipment Ltd',
                'company_description' => 'Specialized in high-quality marine engines and navigation systems',
                'city' => 'Lagos',
                'state' => 'Lagos',
            ],
            [
                'name' => 'Mary Adeleke',
                'email' => 'mary@deepblue.ng',
                'company_name' => 'Deep Blue Marine Supplies',
                'company_description' => 'Your trusted partner for marine safety and communication equipment',
                'city' => 'Port Harcourt',
                'state' => 'Rivers',
            ],
            [
                'name' => 'Ibrahim Mohammed',
                'email' => 'ibrahim@northernmarine.ng',
                'company_name' => 'Northern Marine Solutions',
                'company_description' => 'Comprehensive marine equipment solutions for the northern region',
                'city' => 'Kano',
                'state' => 'Kano',
            ],
            [
                'name' => 'Grace Eze',
                'email' => 'grace@coastalequip.ng',
                'company_name' => 'Coastal Equipment Hub',
                'company_description' => 'Premium marine equipment distributor serving the coastal regions',
                'city' => 'Calabar',
                'state' => 'Cross River',
            ],
        ];

        foreach ($sellers as $sellerData) {
            $user = User::updateOrCreate(
                ['email' => $sellerData['email']],
                [
                    'name' => $sellerData['name'],
                    'email' => $sellerData['email'],
                    'password' => Hash::make('seller123'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign role using Spatie's method
            $user->assignRole($sellerRole);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'full_name' => $sellerData['name'],
                    'role' => 'seller',
                    'is_active' => true,
                    'is_verified' => true,
                    'company_name' => $sellerData['company_name'],
                    'company_description' => $sellerData['company_description'],
                    'phone' => '+234-' . rand(800, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999),
                    'address' => rand(1, 999) . ' Marine Street',
                    'city' => $sellerData['city'],
                    'state' => $sellerData['state'],
                    'country' => 'Nigeria',
                ]
            );
        }

        // Create sample buyers
        $buyers = [
            [
                'name' => 'Captain Samuel Udo',
                'email' => 'samuel@fishingfleet.ng',
                'city' => 'Warri',
                'state' => 'Delta',
            ],
            [
                'name' => 'Chief Engineer Kemi Balogun',
                'email' => 'kemi@shippingco.ng',
                'city' => 'Lagos',
                'state' => 'Lagos',
            ],
            [
                'name' => 'Fisherman Tunde Alabi',
                'email' => 'tunde@fisherfolk.ng',
                'city' => 'Badagry',
                'state' => 'Lagos',
            ],
        ];

        foreach ($buyers as $buyerData) {
            $user = User::updateOrCreate(
                ['email' => $buyerData['email']],
                [
                    'name' => $buyerData['name'],
                    'email' => $buyerData['email'],
                    'password' => Hash::make('user123'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign role using Spatie's method
            $user->assignRole($userRole);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'full_name' => $buyerData['name'],
                    'role' => 'user',
                    'is_active' => true,
                    'is_verified' => true,
                    'phone' => '+234-' . rand(700, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999),
                    'address' => rand(1, 999) . ' Fishermen Street',
                    'city' => $buyerData['city'],
                    'state' => $buyerData['state'],
                    'country' => 'Nigeria',
                ]
            );
        }
    }
}