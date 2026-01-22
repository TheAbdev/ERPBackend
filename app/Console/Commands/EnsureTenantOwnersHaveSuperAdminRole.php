<?php

namespace App\Console\Commands;

use App\Core\Models\Role;
use App\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

class EnsureTenantOwnersHaveSuperAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:ensure-owners-have-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure all tenant owners have super_admin role assigned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ensuring all tenant owners have super_admin role...');

        $tenants = Tenant::whereNotNull('owner_user_id')->get();
        $updatedCount = 0;

        foreach ($tenants as $tenant) {
            $owner = User::find($tenant->owner_user_id);
            
            if (!$owner) {
                $this->warn("  ⚠ Tenant ID {$tenant->id} has owner_user_id {$tenant->owner_user_id} but user not found");
                continue;
            }

            // Ensure super_admin role exists for this tenant
            $superAdminRole = Role::where('tenant_id', $tenant->id)
                ->where('slug', 'super_admin')
                ->first();

            if (!$superAdminRole) {
                $this->warn("  ⚠ Tenant ID {$tenant->id} does not have super_admin role. Creating...");
                $superAdminRole = Role::create([
                    'tenant_id' => $tenant->id,
                    'slug' => 'super_admin',
                    'name' => 'Super Admin',
                    'description' => 'Tenant owner with full access to manage the tenant',
                    'is_system' => true,
                ]);
                
                // Sync all tenant-level permissions
                $allPermissions = \App\Core\Models\Permission::where('slug', '!=', 'platform.manage')
                    ->where('slug', 'not like', 'core.tenants.%')
                    ->where('slug', 'not like', 'core.audit_logs.%')
                    ->get();
                
                if ($allPermissions->isNotEmpty()) {
                    $superAdminRole->permissions()->sync($allPermissions->pluck('id')->toArray());
                }
            }

            // Check if owner has super_admin role
            $hasRole = $owner->roles()
                ->where('roles.id', $superAdminRole->id)
                ->wherePivot('tenant_id', $tenant->id)
                ->exists();

            if (!$hasRole) {
                $owner->roles()->attach($superAdminRole->id, [
                    'tenant_id' => $tenant->id,
                ]);
                $updatedCount++;
                $this->info("  ✅ Assigned super_admin role to owner (User ID: {$owner->id}) for Tenant ID: {$tenant->id}");
            } else {
                $this->line("  ✓ Owner (User ID: {$owner->id}) already has super_admin role for Tenant ID: {$tenant->id}");
            }
        }

        $this->info("Successfully updated {$updatedCount} tenant owner(s).");
        return 0;
    }
}













