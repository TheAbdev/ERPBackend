<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed permissions first (required for other seeders)
        $this->call([
            PermissionSeeder::class,
            SuperAdminSeeder::class,
            AddECommercePermissionsSeeder::class,
         //   ECommerceThemeSeeder::class,
         //   SiteOwnerSeeder::class,
        ]);
    }
}
