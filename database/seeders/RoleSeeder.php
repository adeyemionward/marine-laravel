<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to all system features and administrative functions',
                'permissions' => [
                    'manage_users',
                    'manage_roles',
                    'manage_sellers',
                    'manage_listings',
                    'view_analytics',
                    'manage_system_settings',
                    'moderate_content',
                    'manage_transactions',
                    'access_admin_dashboard'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'seller',
                'display_name' => 'Seller',
                'description' => 'Can create and manage product listings, view sales analytics',
                'permissions' => [
                    'create_listings',
                    'edit_own_listings',
                    'delete_own_listings',
                    'view_own_analytics',
                    'manage_own_profile',
                    'respond_to_messages',
                    'access_seller_dashboard'
                ],
                'is_active' => true,
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Regular user who can browse and purchase products',
                'permissions' => [
                    'browse_products',
                    'purchase_products',
                    'manage_own_profile',
                    'send_messages',
                    'leave_reviews',
                    'save_favorites',
                    'access_user_dashboard'
                ],
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}
