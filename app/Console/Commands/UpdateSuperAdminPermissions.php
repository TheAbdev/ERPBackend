<?php

namespace App\Console\Commands;

use App\Core\Models\Role;
use App\Core\Models\Permission;
use Illuminate\Console\Command;

class UpdateSuperAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:update-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update super_admin roles to sync all tenant-level permissions and exclude platform-level permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating super_admin roles...');

        // Get all super_admin roles
        $superAdminRoles = Role::where('slug', 'super_admin')->get();

        if ($superAdminRoles->isEmpty()) {
            $this->warn('No super_admin roles found.');
            return 0;
        }

        // Get all permissions that are not platform-level
        $allowedPermissions = Permission::where('slug', '!=', 'platform.manage')
            ->where('slug', 'not like', 'core.tenants.%')
            ->where('slug', 'not like', 'core.audit_logs.%')
            ->get();

        $updatedCount = 0;

        foreach ($superAdminRoles as $role) {
            // Get current permissions
            $currentPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

            // Get allowed permission IDs
            $allowedPermissionIds = $allowedPermissions->pluck('id')->toArray();

            // Find permissions to remove (platform-level)
            $permissionsToRemove = array_diff($currentPermissionIds, $allowedPermissionIds);

            // Find permissions to add (new tenant-level permissions)
            $permissionsToAdd = array_diff($allowedPermissionIds, $currentPermissionIds);

            $hasChanges = false;

            if (!empty($permissionsToRemove)) {
                // Remove platform-level permissions
                $role->permissions()->detach($permissionsToRemove);
                $hasChanges = true;
                $this->info("  - Removed " . count($permissionsToRemove) . " platform-level permission(s)");
            }

            if (!empty($permissionsToAdd)) {
                // Add new tenant-level permissions
                $role->permissions()->attach($permissionsToAdd);
                $hasChanges = true;
                $this->info("  - Added " . count($permissionsToAdd) . " new permission(s)");
            }

            // Always sync to ensure all allowed permissions are present
            $role->permissions()->sync($allowedPermissionIds);

            if ($hasChanges) {
                $updatedCount++;
                $this->info("✅ Updated super_admin role for tenant ID: {$role->tenant_id}");
            } else {
                $this->line("  ✓ super_admin role for tenant ID: {$role->tenant_id} is up to date");
            }
        }

        $this->info("Successfully updated {$updatedCount} super_admin role(s).");
        return 0;
    }
}
