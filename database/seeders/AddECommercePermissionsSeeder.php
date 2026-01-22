<?php

namespace Database\Seeders;

use App\Core\Models\Permission;
use App\Core\Models\Role;
use Illuminate\Database\Seeder;

class AddECommercePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🛒 Adding E-Commerce permissions...');

        // E-Commerce permissions
        $ecommercePermissions = [
            'ecommerce.stores.view',
            'ecommerce.stores.viewAny',
            'ecommerce.stores.create',
            'ecommerce.stores.update',
            'ecommerce.stores.delete',
            'ecommerce.themes.view',
            'ecommerce.themes.viewAny',
            'ecommerce.themes.create',
            'ecommerce.themes.update',
            'ecommerce.themes.delete',
            'ecommerce.orders.view',
            'ecommerce.orders.viewAny',
            'ecommerce.orders.update',
            'ecommerce.pages.view',
            'ecommerce.pages.viewAny',
            'ecommerce.pages.create',
            'ecommerce.pages.update',
            'ecommerce.pages.delete',
            'ecommerce.content_blocks.view',
            'ecommerce.content_blocks.viewAny',
            'ecommerce.content_blocks.create',
            'ecommerce.content_blocks.update',
            'ecommerce.content_blocks.delete',
        ];

        // Create permissions from config
        $permissionsFromConfig = config('permissions.permissions', []);

        foreach ($permissionsFromConfig as $permissionSlug) {
            // Only process E-Commerce permissions
            if (strpos($permissionSlug, 'ecommerce.') !== 0) {
                continue;
            }

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

        $this->command->info('✅ E-Commerce permissions created');

        // Update all super_admin roles to include E-Commerce permissions
        $this->command->info('🔄 Updating super_admin roles...');

        $superAdminRoles = Role::where('slug', 'super_admin')->get();
        $ecommercePermissionIds = Permission::where('slug', 'like', 'ecommerce.%')
            ->pluck('id')
            ->toArray();

        foreach ($superAdminRoles as $role) {
            // Get current permissions
            $currentPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

            // Merge with E-Commerce permissions
            $allPermissionIds = array_unique(array_merge($currentPermissionIds, $ecommercePermissionIds));

            // Sync permissions
            $role->permissions()->sync($allPermissionIds);

            $this->command->info("✅ Updated super_admin role for tenant ID: {$role->tenant_id}");
        }

        $this->command->info('✅ All super_admin roles updated with E-Commerce permissions');
    }
}

