<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-superadmin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign super admin role to a user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found!");
            return 1;
        }

        // Find or create super_admin role
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'api'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Has unrestricted access to all features and settings'
            ]
        );

        // Assign super admin role using Spatie (this will also remove existing roles)
        $user->syncRoles([$superAdminRole]);

        // Get all permissions and assign them to the user
        $allPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'api')->get();
        $user->syncPermissions($allPermissions);

        $this->info("✅ Super admin role assigned to {$user->name} ({$email})");
        $this->info("Assigned permissions: {$allPermissions->count()}");
        $this->info("This user now has unrestricted access to all features.");

        return 0;
    }
}
