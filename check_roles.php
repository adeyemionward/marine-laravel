<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "=== Current Roles and Permissions State ===\n\n";

echo "Total Roles: " . Role::count() . "\n";
echo "Total Permissions: " . Permission::count() . "\n\n";

echo "=== Roles Detail ===\n";
$roles = Role::with('permissions')->get();

foreach ($roles as $role) {
    echo "\n{$role->name} (guard: {$role->guard_name})\n";
    echo "  Permissions count: " . $role->permissions->count() . "\n";

    if ($role->permissions->count() > 0) {
        echo "  First 5 permissions: " . $role->permissions->take(5)->pluck('name')->join(', ') . "\n";
    }
}

echo "\n=== Missing Roles from Seeder ===\n";
$expectedRoles = ['super_admin', 'admin', 'content_manager', 'moderator', 'user_support', 'financial_manager'];
$existingRoles = Role::pluck('name')->toArray();

foreach ($expectedRoles as $expectedRole) {
    if (!in_array($expectedRole, $existingRoles)) {
        echo "- {$expectedRole} (MISSING)\n";
    } else {
        echo "- {$expectedRole} (EXISTS)\n";
    }
}

echo "\n=== Expected vs Actual Permissions ===\n";
$expectedPermissionsCount = 79; // From the seeder
$actualPermissionsCount = Permission::count();

echo "Expected: {$expectedPermissionsCount}\n";
echo "Actual: {$actualPermissionsCount}\n";
echo "Missing: " . ($expectedPermissionsCount - $actualPermissionsCount) . "\n";
