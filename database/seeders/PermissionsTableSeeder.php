<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(){
        $permissions = [
            'role-create',
            'role-list',
            'role-view',
            'role-update',
            'role-delete',

            'user-create',
            'user-list',
            'user-view',
            'user-update',
            'user-delete',

            'product-list',
            'product-view',
            'product-update',
            'product-update-status',
            'product-delete',

            'order-list',
            'order-view',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $this->command->info('Permissions updated successfully!');
    }
}
