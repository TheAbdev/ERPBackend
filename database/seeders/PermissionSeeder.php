<?php

namespace Database\Seeders;

use App\Core\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = config('permissions.permissions');

        foreach ($permissions as $permissionSlug) {
            // Parse permission: {module}.{resource}.{action}
            $parts = explode('.', $permissionSlug);

            if (count($parts) < 3) {
                continue;
            }

            $module = $parts[0];
            $action = $parts[count($parts) - 1];
            $resource = implode('.', array_slice($parts, 1, -1));

            $resourceLabel = ucwords(str_replace(['.', '_', '-'], ' ', $resource));
            $actionLabel = ucwords(str_replace(['_', '-'], ' ', $action));

            // Generate human-readable name
            $name = ucfirst($module) . ' - ' . $resourceLabel . ' - ' . $actionLabel;

            // Generate description
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

        $this->command->info('Permissions seeded successfully.');
    }
}




