<?php

namespace Database\Seeders;

use App\Core\Models\Permission;
use App\Core\Models\Role;
use App\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SiteOwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('🚀 Starting Site Owner setup...');
            $this->command->newLine();

            // Step 1: Ensure platform.manage permission exists
            $platformPermission = Permission::firstOrCreate(
                ['slug' => 'platform.manage'],
                [
                    'name' => 'Platform - Manage - All',
                    'module' => 'platform',
                    'description' => 'Allows full access to platform-level tenant management',
                ]
            );

            $this->command->info("✅ Permission 'platform.manage' ensured (ID: {$platformPermission->id})");

            // Step 2: Get or create main tenant (for site owner to belong to)
            $mainTenant = Tenant::where('slug', 'main')->first();

            if (! $mainTenant) {
                $mainTenant = Tenant::create([
                    'name' => env('TENANT_NAME', 'Main Company'),
                    'slug' => 'main',
                    'subdomain' => env('TENANT_SUBDOMAIN', 'main'),
                    'domain' => env('TENANT_DOMAIN'),
                    'status' => 'active',
                    'settings' => [
                        'is_main' => true,
                    ],
                ]);
                $this->command->info("✅ Main tenant created (ID: {$mainTenant->id})");
            } else {
                $this->command->info("✅ Main tenant found (ID: {$mainTenant->id})");
            }

            // Step 3: Create or update site_owner role
            // Note: Site Owner role belongs to main tenant but has platform-wide access
            $siteOwnerRole = Role::updateOrCreate(
                [
                    'tenant_id' => $mainTenant->id,
                    'slug' => 'site_owner',
                ],
                [
                    'name' => 'Site Owner',
                    'description' => 'Platform Owner with full access to manage all tenants and system-wide operations',
                    'is_system' => true,
                ]
            );

            $this->command->info("✅ Site Owner role created/updated (ID: {$siteOwnerRole->id})");

            // Step 4: Assign platform.manage permission to site_owner role
            $siteOwnerRole->permissions()->syncWithoutDetaching([$platformPermission->id]);

            // Also assign ALL other permissions for full system access
            $allPermissions = Permission::all();
            $siteOwnerRole->permissions()->sync($allPermissions->pluck('id')->toArray());

            $this->command->info("✅ Assigned {$allPermissions->count()} permissions to Site Owner role (including platform.manage)");

            // Step 5: Get site owner credentials from .env
            $siteOwnerEmail = env('SITE_OWNER_EMAIL', env('SUPER_ADMIN_EMAIL'));
            $siteOwnerPassword = env('SITE_OWNER_PASSWORD', env('SUPER_ADMIN_PASSWORD'));
            $siteOwnerName = env('SITE_OWNER_NAME', 'Site Owner');

            // Use defaults if not set, but warn the user
            if (empty($siteOwnerEmail)) {
                $siteOwnerEmail = 'siteowner@' . ($mainTenant->domain ?? 'example.com');
                $this->command->warn("⚠️  SITE_OWNER_EMAIL not set in .env, using default: {$siteOwnerEmail}");
                $this->command->warn("   Please set SITE_OWNER_EMAIL in your .env file for production use.");
            }

            if (empty($siteOwnerPassword)) {
                $siteOwnerPassword = 'changeme123!';
                $this->command->warn("⚠️  SITE_OWNER_PASSWORD not set in .env, using default password: changeme123!");
                $this->command->warn("   ⚠️  SECURITY WARNING: Please change this password immediately!");
                $this->command->warn("   Set SITE_OWNER_PASSWORD in your .env file for production use.");
            }

            // Step 6: Create or update site owner user
            $siteOwner = User::updateOrCreate(
                [
                    'email' => $siteOwnerEmail,
                ],
                [
                    'name' => $siteOwnerName,
                    'password' => Hash::make($siteOwnerPassword),
                    'tenant_id' => $mainTenant->id,
                    'email_verified_at' => now(),
                ]
            );

            $this->command->info("✅ Site Owner user created/updated (ID: {$siteOwner->id}, Email: {$siteOwner->email})");

            // Step 7: Assign site_owner role to user
            $existingRoleIds = DB::table('user_role')
                ->where('user_id', $siteOwner->id)
                ->where('tenant_id', $mainTenant->id)
                ->pluck('role_id')
                ->toArray();

            if (!in_array($siteOwnerRole->id, $existingRoleIds)) {
                // Attach site_owner role with tenant_id in pivot
                DB::table('user_role')->insert([
                    'user_id' => $siteOwner->id,
                    'role_id' => $siteOwnerRole->id,
                    'tenant_id' => $mainTenant->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("✅ Site Owner role assigned to user");
            } else {
                $this->command->info("✅ Site Owner role already assigned to user");
            }

            // Step 8: Set this user as owner of main tenant (optional)
            if (! $mainTenant->owner_user_id) {
                $mainTenant->update(['owner_user_id' => $siteOwner->id]);
                $this->command->info("✅ Site Owner set as owner of main tenant");
            }

            $this->command->newLine();
            $this->command->info('═══════════════════════════════════════════════════════════');
            $this->command->info('✅ Site Owner setup completed successfully!');
            $this->command->info('═══════════════════════════════════════════════════════════');
            $this->command->newLine();
            $this->command->info("📋 Summary:");
            $this->command->info("   Tenant: {$mainTenant->name} (ID: {$mainTenant->id})");
            $this->command->info("   User: {$siteOwner->name} ({$siteOwner->email})");
            $this->command->info("   Role: {$siteOwnerRole->name} ({$allPermissions->count()} permissions)");
            $this->command->info("   Platform Permission: platform.manage ✅");
            $this->command->newLine();
            $this->command->info("🔐 Login Credentials:");
            $this->command->info("   Email: {$siteOwner->email}");
            $this->command->info("   Password: " . (env('SITE_OWNER_PASSWORD') ? '*** (from .env)' : 'changeme123! (DEFAULT - CHANGE THIS!)'));
            $this->command->newLine();
            $this->command->info("🌐 API Access:");
            $this->command->info("   Platform Routes: /api/platform/tenants");
            $this->command->info("   Use this token after login to access platform endpoints");
            $this->command->newLine();
        });
    }
}

