<?php

namespace Database\Seeders;

use App\Core\Models\Permission;
use App\Core\Models\Role;
use App\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Step 1: Create or update main tenant
            $tenant = Tenant::updateOrCreate(
                [
                    'slug' => 'main',
                ],
                [
                    'name' => env('TENANT_NAME', 'Main Company'),
                    'subdomain' => env('TENANT_SUBDOMAIN', 'main'),
                    'domain' => env('TENANT_DOMAIN'),
                    'status' => 'active',
                    'settings' => [
                        'is_main' => true,
                    ],
                ]
            );

            $this->command->info("Tenant '{$tenant->name}' created/updated (ID: {$tenant->id})");

            // Step 2: Ensure all permissions exist (run PermissionSeeder first if needed)
            $permissionsFromConfig = config('permissions.permissions', []);

            foreach ($permissionsFromConfig as $permissionSlug) {
                $parts = explode('.', $permissionSlug);

                if (count($parts) !== 3) {
                    continue;
                }

                [$module, $resource, $action] = $parts;

                $name = ucfirst($module) . ' - ' . ucfirst($resource) . ' - ' . ucfirst($action);
                $description = "Allows {$action} action on {$resource} resource in {$module} module";

                Permission::firstOrCreate(
                    ['slug' => $permissionSlug],
                    [
                        'name' => $name,
                        'module' => $module,
                        'description' => $description,
                    ]
                );
            }

            // Get all permissions (from config and any additional ones in database)
            $allPermissions = Permission::all();
            $this->command->info("Found {$allPermissions->count()} permissions in the system");

            // Step 3: Create or update site_owner role (for Site Owner)
            // Site Owner role belongs to main tenant but has platform-wide access
            // Note: User has tenant_id = null, but role belongs to main tenant
            $siteOwnerRole = Role::updateOrCreate(
                [
                    'tenant_id' => $tenant->id, // Role belongs to main tenant
                    'slug' => 'site_owner',
                ],
                [
                    'name' => 'Site Owner',
                    'description' => 'Platform Owner with full access to manage all tenants and system-wide operations',
                    'is_system' => true,
                ]
            );

            $this->command->info("Site Owner role created/updated (ID: {$siteOwnerRole->id})");

            // Step 4: Ensure platform.manage permission exists
            $platformPermission = \App\Core\Models\Permission::firstOrCreate(
                ['slug' => 'platform.manage'],
                [
                    'name' => 'Platform - Manage - All',
                    'module' => 'platform',
                    'description' => 'Allows full access to platform-level tenant management',
                ]
            );

            // Step 5: Assign ALL permissions to site_owner role (including platform.manage)
            $permissionIds = $allPermissions->pluck('id')->toArray();
            // Add platform.manage if not already in the list
            if (!in_array($platformPermission->id, $permissionIds)) {
                $permissionIds[] = $platformPermission->id;
            }
            $siteOwnerRole->permissions()->sync($permissionIds);

            $this->command->info("Assigned " . count($permissionIds) . " permissions to Site Owner role (including platform.manage)");

            // Step 5: Get super admin email and password from .env
            $superAdminEmail = env('SUPER_ADMIN_EMAIL');
            $superAdminPassword = env('SUPER_ADMIN_PASSWORD');

            // Use defaults if not set, but warn the user
            if (empty($superAdminEmail)) {
                $superAdminEmail = 'admin@' . ($tenant->domain ?? 'example.com');
                $this->command->warn("⚠️  SUPER_ADMIN_EMAIL not set in .env, using default: {$superAdminEmail}");
                $this->command->warn("   Please set SUPER_ADMIN_EMAIL in your .env file for production use.");
            }

            if (empty($superAdminPassword)) {
                $superAdminPassword = 'changeme123!';
                $this->command->warn("⚠️  SUPER_ADMIN_PASSWORD not set in .env, using default password: changeme123!");
                $this->command->warn("   ⚠️  SECURITY WARNING: Please change this password immediately!");
                $this->command->warn("   Set SUPER_ADMIN_PASSWORD in your .env file for production use.");
            }

            // Step 6: Create or update super admin user (Site Owner)
            // Site Owner belongs to main tenant but has platform-wide access
            // The user's tenant_id = main tenant, but they can access all tenants
            $superAdmin = User::updateOrCreate(
                [
                    'email' => $superAdminEmail,
                ],
                [
                    'name' => env('SUPER_ADMIN_NAME', 'System Owner'),
                    'password' => Hash::make($superAdminPassword),
                    'tenant_id' => $tenant->id, // Site Owner belongs to main tenant
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info("Super Admin user created/updated (ID: {$superAdmin->id}, Email: {$superAdmin->email})");

            // Step 7: Assign site_owner role to user
            // Site Owner user belongs to main tenant (tenant_id = main tenant)
            // Role also belongs to main tenant
            // In pivot table, we use main tenant_id (same as user's tenant_id and role's tenant_id)
            $existingRoleIds = DB::table('user_role')
                ->where('user_id', $superAdmin->id)
                ->where('role_id', $siteOwnerRole->id)
                ->where('tenant_id', $tenant->id)
                ->pluck('role_id')
                ->toArray();

            if (!in_array($siteOwnerRole->id, $existingRoleIds)) {
                // Attach site_owner role with main tenant_id in pivot
                // Both user and role belong to main tenant, so pivot tenant_id = main tenant
                DB::table('user_role')->insert([
                    'user_id' => $superAdmin->id,
                    'role_id' => $siteOwnerRole->id,
                    'tenant_id' => $tenant->id, // Use main tenant_id (same as user's tenant_id)
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("Site Owner role assigned to user");
            } else {
                $this->command->info("Site Owner role already assigned to user");
            }

            $this->command->newLine();
            $this->command->info('✅ Site Owner setup completed successfully!');
            $this->command->info("   User: {$superAdmin->name} ({$superAdmin->email})");
            $this->command->info("   Tenant: {$tenant->name} (ID: {$tenant->id})");
            $this->command->info("   Role: {$siteOwnerRole->name} (" . count($permissionIds) . " permissions)");
            $this->command->info("   Note: Site Owner belongs to main tenant but has platform-wide access");
        });
    }
}

