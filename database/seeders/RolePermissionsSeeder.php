<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for role management
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'role-assign',
            'permission-list',
            'permission-assign',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['name' => $permission, 'guard_name' => 'api']
            );
        }

        $this->command->info('Role management permissions created successfully!');

        // Optionally assign these permissions to admin role if it exists
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();

        if ($adminRole) {
            $adminRole->syncPermissions($permissions);
            $this->command->info('Permissions assigned to admin role!');
        } else {
            $this->command->warn('Admin role not found. Please assign these permissions manually.');
        }
    }
}
