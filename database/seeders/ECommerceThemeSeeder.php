<?php

namespace Database\Seeders;

use App\Modules\ECommerce\Models\Theme;
use App\Modules\ECommerce\Services\ThemeService;
use Illuminate\Database\Seeder;

class ECommerceThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active tenants
        $tenants = \App\Core\Models\Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            // Set tenant context
            app(\App\Core\Services\TenantContext::class)->setTenant($tenant);

            // Create default themes using ThemeService
            $themeService = app(ThemeService::class);
            $themeService->createDefaultThemes($tenant->id);
        }
    }
}



















