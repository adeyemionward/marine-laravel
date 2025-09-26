<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\EquipmentListing;
use Illuminate\Support\Facades\DB;

class SyncSellerProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seller:sync-roles-profiles {--dry-run : Show what would be done without making changes} {--auto-fix : Automatically fix inconsistencies without prompting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize seller roles and profiles ensuring consistency: Users must have seller_profile to have seller role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analyzing seller role-profile consistency...');
        $isDryRun = $this->option('dry-run');
        $autoFix = $this->option('auto-fix');

        DB::beginTransaction();

        try {
            // Get role IDs for efficiency
            $userRoleId = Role::where('name', 'user')->first()?->id;
            $sellerRoleId = Role::where('name', 'seller')->first()?->id;

            if (!$userRoleId || !$sellerRoleId) {
                $this->error('❌ Required roles (user/seller) not found in database');
                return 1;
            }

            // ANALYSIS PHASE: Identify all inconsistencies
            $issues = $this->analyzeInconsistencies($userRoleId, $sellerRoleId);

            if (empty($issues['users_with_seller_role_no_profile']) &&
                empty($issues['users_with_profile_no_seller_role']) &&
                empty($issues['orphaned_profiles'])) {
                $this->info('✅ All seller roles and profiles are perfectly synchronized!');
                DB::rollback();
                return 0;
            }

            // Display analysis results
            $this->displayAnalysisResults($issues);

            if ($isDryRun) {
                $this->info("\n🔸 This is a dry run. No changes were made.");
                $this->info("Run without --dry-run to apply fixes.");
                DB::rollback();
                return 0;
            }

            // FIXING PHASE: Apply the new rule
            $this->info("\n🔧 Applying fixes based on the rule: 'Only users with SellerProfile can have seller role'");

            $totalChanges = 0;

            // Fix 1: Users with seller role but no profile → Convert to regular users
            $totalChanges += $this->fixUsersWithSellerRoleNoProfile($issues['users_with_seller_role_no_profile'], $userRoleId, $autoFix);

            // Fix 2: Users with profiles but wrong role → Give them seller role (if they have listings/activity)
            $totalChanges += $this->fixUsersWithProfileNoSellerRole($issues['users_with_profile_no_seller_role'], $sellerRoleId, $autoFix);

            // Fix 3: Clean up orphaned profiles (profiles without users)
            $totalChanges += $this->cleanupOrphanedProfiles($issues['orphaned_profiles'], $autoFix);

            if ($totalChanges > 0) {
                DB::commit();
                $this->info("\n🎉 Successfully synchronized {$totalChanges} seller roles/profiles!");
                $this->info("✅ System is now consistent: seller role ⟺ seller profile");
            } else {
                DB::rollback();
                $this->info("\n📝 No changes were made.");
            }

            return 0;

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("❌ Error during synchronization: {$e->getMessage()}");
            return 1;
        }
    }

    private function analyzeInconsistencies($userRoleId, $sellerRoleId): array
    {
        // Users with seller role but no seller profile
        $usersWithSellerRoleNoProfile = User::where('role_id', $sellerRoleId)
            ->whereDoesntHave('sellerProfile')
            ->with('role')
            ->get();

        // Users with seller profile but not seller role
        $usersWithProfileNoSellerRole = User::where('role_id', '!=', $sellerRoleId)
            ->whereHas('sellerProfile')
            ->with(['role', 'sellerProfile'])
            ->get();

        // Orphaned profiles (profiles without users)
        $orphanedProfiles = SellerProfile::whereDoesntHave('user')->get();

        return [
            'users_with_seller_role_no_profile' => $usersWithSellerRoleNoProfile,
            'users_with_profile_no_seller_role' => $usersWithProfileNoSellerRole,
            'orphaned_profiles' => $orphanedProfiles,
        ];
    }

    private function displayAnalysisResults($issues): void
    {
        $this->warn("📊 Found the following inconsistencies:");

        // Issue 1: Users with seller role but no profile
        if ($issues['users_with_seller_role_no_profile']->isNotEmpty()) {
            $count = $issues['users_with_seller_role_no_profile']->count();
            $this->warn("\n1️⃣  Users with SELLER ROLE but NO PROFILE ({$count}):");
            foreach ($issues['users_with_seller_role_no_profile'] as $user) {
                $listingsCount = EquipmentListing::where('seller_id', $user->id)->count();
                $this->line("   → User #{$user->id}: {$user->name} ({$user->email}) - {$listingsCount} listings");
            }
            $this->info("   🔧 FIX: Will convert these users back to 'user' role for backward compatibility");
        }

        // Issue 2: Users with profile but wrong role
        if ($issues['users_with_profile_no_seller_role']->isNotEmpty()) {
            $count = $issues['users_with_profile_no_seller_role']->count();
            $this->warn("\n2️⃣  Users with SELLER PROFILE but WRONG ROLE ({$count}):");
            foreach ($issues['users_with_profile_no_seller_role'] as $user) {
                $listingsCount = EquipmentListing::where('seller_id', $user->id)->count();
                $currentRole = $user->role ? $user->role->name : 'NO ROLE';
                $this->line("   → User #{$user->id}: {$user->name} (Role: {$currentRole}) - {$listingsCount} listings");
            }
            $this->info("   🔧 FIX: Will promote these users to 'seller' role since they have profiles");
        }

        // Issue 3: Orphaned profiles
        if ($issues['orphaned_profiles']->isNotEmpty()) {
            $count = $issues['orphaned_profiles']->count();
            $this->warn("\n3️⃣  ORPHANED PROFILES (profiles without users) ({$count}):");
            foreach ($issues['orphaned_profiles'] as $profile) {
                $this->line("   → Profile #{$profile->id}: {$profile->business_name} (User ID: {$profile->user_id})");
            }
            $this->info("   🔧 FIX: Will delete these orphaned profiles");
        }
    }

    private function fixUsersWithSellerRoleNoProfile($users, $userRoleId, $autoFix): int
    {
        if ($users->isEmpty()) return 0;

        if (!$autoFix) {
            $this->warn("\n🔄 Processing users with seller role but no profile...");
            if (!$this->confirm("Convert {$users->count()} users from 'seller' to 'user' role?")) {
                $this->info("Skipped role conversions.");
                return 0;
            }
        }

        $converted = 0;
        foreach ($users as $user) {
            try {
                $user->update(['role_id' => $userRoleId]);
                $this->info("✅ Converted {$user->name} from seller to user role");
                $converted++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to convert {$user->name}: {$e->getMessage()}");
            }
        }

        return $converted;
    }

    private function fixUsersWithProfileNoSellerRole($users, $sellerRoleId, $autoFix): int
    {
        if ($users->isEmpty()) return 0;

        if (!$autoFix) {
            $this->warn("\n🔄 Processing users with seller profile but wrong role...");
            if (!$this->confirm("Promote {$users->count()} users to 'seller' role?")) {
                $this->info("Skipped role promotions.");
                return 0;
            }
        }

        $promoted = 0;
        foreach ($users as $user) {
            try {
                $user->update(['role_id' => $sellerRoleId]);
                $currentRole = $user->role ? $user->role->name : 'NO ROLE';
                $this->info("✅ Promoted {$user->name} from {$currentRole} to seller role");
                $promoted++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to promote {$user->name}: {$e->getMessage()}");
            }
        }

        return $promoted;
    }

    private function cleanupOrphanedProfiles($profiles, $autoFix): int
    {
        if ($profiles->isEmpty()) return 0;

        if (!$autoFix) {
            $this->warn("\n🗑️  Processing orphaned profiles...");
            if (!$this->confirm("Delete {$profiles->count()} orphaned profiles?")) {
                $this->info("Skipped orphaned profile cleanup.");
                return 0;
            }
        }

        $deleted = 0;
        foreach ($profiles as $profile) {
            try {
                $profile->delete();
                $this->info("✅ Deleted orphaned profile #{$profile->id}: {$profile->business_name}");
                $deleted++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to delete profile #{$profile->id}: {$e->getMessage()}");
            }
        }

        return $deleted;
    }
}
