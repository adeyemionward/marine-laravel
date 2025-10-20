# Backend Permission System Setup Guide

## Overview
This Laravel backend uses **Spatie Laravel Permission** package for role-based access control with permissions.

## What Was Added

### 1. Updated User Model (`app/Models/User.php`)
- Added `isSuperAdmin()` method - checks if user is super admin
- Updated `isAdmin()` method - now includes super_admin check
- Overridden `hasPermission()` - super admins bypass all permission checks
- Overridden `hasPermissionTo()` - Spatie method override for super admin

### 2. Updated UserResource (`app/Http/Resources/UserResource.php`)
- Now returns `permissions` array from Spatie
- Returns `roles` array from Spatie
- Returns role permissions in role object

### 3. Created Permissions and Roles Seeder (`database/seeders/PermissionsAndRolesSeeder.php`)
Seeds all permissions and creates default roles:
- **super_admin** - Unrestricted access (bypasses all permission checks)
- **admin** - Full access except system-critical features
- **content_manager** - Content and listings management
- **moderator** - Content and user moderation
- **user_support** - User support and subscriptions
- **financial_manager** - Financial management

### 4. Created Artisan Command (`app/Console/Commands/AssignSuperAdmin.php`)
Command to assign super admin role to any user by email.

## Setup Instructions

### Step 1: Run Migrations (if not already done)
```bash
php artisan migrate
```

### Step 2: Seed Permissions and Roles
```bash
php artisan db:seed --class=PermissionsAndRolesSeeder
```

This will create:
- 60+ permissions covering all admin features
- 6 default roles with appropriate permissions

### Step 3: Assign Super Admin to Your User
```bash
php artisan user:make-superadmin admin@marine.ng
```

Replace `admin@marine.ng` with your admin email. This user will have:
- Unrestricted access to all features
- Bypass all permission checks
- Full control over the system

### Step 4: Test the Setup
Login with your super admin user and check the response:
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@marine.ng",
    "role": {
      "name": "super_admin",
      "permissions": [...]
    },
    "permissions": [...],
    "roles": ["super_admin"]
  }
}
```

## Available Permissions

### Dashboard & Analytics
- `view_dashboard`
- `view_analytics`
- `view_system_monitor`
- `view_financial_reports`

### User Management
- `view_users`
- `create_users`
- `edit_users`
- `delete_users`
- `manage_user_roles`
- `view_seller_applications`
- `approve_sellers`
- `view_subscriptions`
- `manage_subscriptions`
- `view_invoices`
- `manage_invoices`
- `view_messages`
- `send_messages`

### Role & Permission Management
- `view_roles`
- `create_roles`
- `edit_roles`
- `delete_roles`
- `view_permissions`
- `assign_permissions`

### Content Management
- `view_listings`
- `create_listings`
- `edit_listings`
- `delete_listings`
- `approve_listings`
- `manage_categories`
- `manage_priority_listings`
- `manage_featured_listings`
- `manage_banners`
- `manage_knowledge_base`

### Financial Management
- `view_financial_management`
- `manage_finances`
- `view_customers_suppliers`
- `manage_customers_suppliers`

### Communications
- `manage_email_config`
- `manage_newsletter`

### System Configuration
- `manage_branding`
- `manage_backups`
- `manage_api_keys`
- `manage_database`
- `manage_settings`

## Creating Custom Roles

### Via Code
```php
use Spatie\Permission\Models\Role;

$role = Role::create([
    'name' => 'sales_manager',
    'guard_name' => 'api',
    'display_name' => 'Sales Manager',
    'description' => 'Manages sales and listings'
]);

$role->givePermissionTo([
    'view_dashboard',
    'view_listings',
    'edit_listings',
    'view_customers_suppliers',
]);
```

### Via Role Management UI
Use the Role & Permission Management page in the admin dashboard.

## Assigning Roles to Users

### Via Artisan Command
```bash
# Make user super admin
php artisan user:make-superadmin user@example.com
```

### Via Code
```php
use App\Models\User;
use Spatie\Permission\Models\Role;

$user = User::find(1);
$role = Role::where('name', 'content_manager')
            ->where('guard_name', 'api')
            ->first();

$user->assignRole($role);

// Sync permissions from role to user
$user->syncPermissions($role->permissions);
```

### Via API
Use the `POST /api/admin/roles/assign/{userId}` endpoint.

## Checking Permissions

### In Controllers
```php
// Check if user has permission
if (!auth()->user()->hasPermissionTo('view_users', 'api')) {
    return response()->json(['error' => 'Unauthorized'], 403);
}

// Super admins automatically pass all permission checks
if (auth()->user()->isSuperAdmin()) {
    // This user has all permissions
}
```

### Using Middleware
```php
Route::middleware(['auth:sanctum', 'permission:view_users'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

### In Blade (if using)
```php
@can('edit_users')
    <button>Edit User</button>
@endcan
```

## Super Admin Features

Users with `super_admin` role:
1. **Bypass ALL permission checks** - No need to assign individual permissions
2. **See all menu items** in frontend - Nothing is hidden
3. **Access all API endpoints** - Even without explicit permissions
4. **Cannot be restricted** - Ultimate access level

## Default Role Hierarchy

1. **super_admin** - God mode, unrestricted access
2. **admin** - Almost full access, except system-critical features
3. **content_manager** - Content and listings only
4. **financial_manager** - Financial features only
5. **moderator** - Moderation features
6. **user_support** - User support features
7. **user** - Regular user (no admin access)
8. **seller** - Seller user (marketplace access)

## Troubleshooting

### Permissions not working?
```bash
# Clear permission cache
php artisan permission:cache-reset

# Or in code
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

### Role not found?
```bash
# Re-run seeder
php artisan db:seed --class=PermissionsAndRolesSeeder
```

### User still can't access features?
1. Check if user has role assigned in `model_has_roles` table
2. Check if role has permissions in `role_has_permissions` table
3. Check guard name is `api` for all roles and permissions
4. Verify API returns permissions in user object

## Database Tables

Spatie creates these tables:
- `permissions` - All available permissions
- `roles` - All roles
- `model_has_permissions` - Direct user permissions
- `model_has_roles` - User role assignments
- `role_has_permissions` - Role permissions

## API Response Example

When a user logs in, they receive:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@marine.ng",
      "role": {
        "id": 1,
        "name": "super_admin",
        "display_name": "Super Administrator",
        "permissions": ["view_dashboard", "view_users", ...]
      },
      "permissions": ["view_dashboard", "view_users", ...],
      "roles": ["super_admin"],
      "is_seller": false
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

## Security Notes

⚠️ **IMPORTANT**:
1. Always validate permissions on the backend
2. Frontend permission checks are for UX only
3. Never trust permission data from the client
4. Protect system-critical permissions (database, API keys, backups)
5. Limit who can create/edit roles
6. Regularly audit super admin users

## Need Help?

- Spatie Documentation: https://spatie.be/docs/laravel-permission
- Check logs: `storage/logs/laravel.log`
- Run tests: `php artisan test`
