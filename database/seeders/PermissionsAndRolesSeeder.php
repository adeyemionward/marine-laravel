<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionsAndRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CRITICAL: Reset cached roles and permissions BEFORE seeding
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Dashboard
            'view_dashboard',
            'view_analytics',
            'view_system_monitor',
            'view_financial_reports',

            // User Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_user_roles',
            'view_seller_applications',
            'approve_sellers',
            'view_subscriptions',
            'manage_subscriptions',
            'view_invoices',
            'manage_invoices',
            'view_messages',
            'send_messages',

            // Role & Permission Management
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'assign_roles',
            'view_permissions',
            'assign_permissions',

            // Content Management
            'view_listings',
            'create_listings',
            'edit_listings',
            'delete_listings',
            'approve_listings',
            'manage_categories',
            'manage_priority_listings',
            'manage_featured_listings',
            'manage_banners',
            'manage_knowledge_base',

            // Financial Management
            'view_financial_management',
            'manage_finances',
            'view_customers_suppliers',
            'manage_customers_suppliers',

            // Communications
            'manage_email_config',
            'manage_newsletter',

            // System Configuration
            'manage_branding',
            'manage_backups',
            'manage_api_keys',
            'manage_database',
            'manage_settings',
        ];

        // Create permissions for 'api' guard
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'api'
            ]);
        }

        // Create Super Admin Role (has all permissions)
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'api'],
            ['display_name' => 'Super Administrator', 'description' => 'Has unrestricted access', 'is_active' => true]
        );

        // Super admin doesn't need explicit permissions - they bypass all checks in the User model
        // But we'll assign them anyway for consistency
        $superAdminRole->syncPermissions($permissions);

        // Create Admin Role (most permissions except system-critical ones)
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api'],
            ['display_name' => 'Administrator', 'description' => 'Most permissions except system-critical', 'is_active' => true]
        );
        $adminPermissions = array_diff($permissions, [
            'manage_database',
            'manage_api_keys',
            'manage_backups',
        ]);
        $adminRole->syncPermissions($adminPermissions);

        // Create Content Manager Role
        $contentManagerRole = Role::firstOrCreate(
            ['name' => 'content_manager', 'guard_name' => 'api'],
            ['display_name' => 'Content Manager', 'description' => 'Manages content and listings', 'is_active' => true]
        );
        $contentManagerRole->syncPermissions([
            'view_dashboard',
            'view_analytics',
            'view_listings',
            'create_listings',
            'edit_listings',
            'delete_listings',
            'approve_listings',
            'manage_categories',
            'manage_priority_listings',
            'manage_featured_listings',
            'manage_banners',
            'manage_knowledge_base',
        ]);

        // Create Moderator Role
        $moderatorRole = Role::firstOrCreate(
            ['name' => 'moderator', 'guard_name' => 'api'],
            ['display_name' => 'Moderator', 'description' => 'Moderates content and users', 'is_active' => true]
        );
        $moderatorRole->syncPermissions([
            'view_dashboard',
            'view_users',
            'view_listings',
            'edit_listings',
            'approve_listings',
            'view_messages',
            'send_messages',
            'view_seller_applications',
        ]);

        // Create User Support Role
        $userSupportRole = Role::firstOrCreate(
            ['name' => 'user_support', 'guard_name' => 'api'],
            ['display_name' => 'User Support', 'description' => 'Provides user support', 'is_active' => true]
        );
        $userSupportRole->syncPermissions([
            'view_dashboard',
            'view_users',
            'view_subscriptions',
            'manage_subscriptions',
            'view_invoices',
            'view_messages',
            'send_messages',
        ]);

        // Create Financial Manager Role
        $financialManagerRole = Role::firstOrCreate(
            ['name' => 'financial_manager', 'guard_name' => 'api'],
            ['display_name' => 'Financial Manager', 'description' => 'Manages financials', 'is_active' => true]
        );
        $financialManagerRole->syncPermissions([
            'view_dashboard',
            'view_financial_reports',
            'view_financial_management',
            'manage_finances',
            'view_customers_suppliers',
            'manage_customers_suppliers',
            'view_invoices',
            'manage_invoices',
            'view_subscriptions',
        ]);

        // Create Seller Role
        $sellerRole = Role::firstOrCreate(
            ['name' => 'seller', 'guard_name' => 'api'],
            ['display_name' => 'Seller', 'description' => 'Can create and manage their own listings', 'is_active' => true]
        );
        $sellerRole->syncPermissions([
            'view_dashboard',
            'view_listings',
            'create_listings',
            'edit_listings',
            'view_messages',
            'send_messages',
        ]);

        // Create User Role
        $userRole = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'api'],
            ['display_name' => 'User', 'description' => 'Regular user with basic access', 'is_active' => true]
        );
        $userRole->syncPermissions([
            'view_listings',
            'view_messages',
            'send_messages',
        ]);

        $this->command->info('Permissions and roles have been seeded successfully!');
        $this->command->info('Created roles:');
        $this->command->info('- super_admin (all permissions, bypasses checks)');
        $this->command->info('- admin (most permissions)');
        $this->command->info('- content_manager (content management)');
        $this->command->info('- moderator (moderation)');
        $this->command->info('- user_support (user support)');
        $this->command->info('- financial_manager (financial management)');
        $this->command->info('- seller (can create and manage own listings)');
        $this->command->info('- user (basic user access)');
    }
}
