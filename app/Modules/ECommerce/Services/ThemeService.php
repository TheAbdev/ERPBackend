<?php

namespace App\Modules\ECommerce\Services;

use App\Core\Services\TenantContext;
use App\Modules\ECommerce\Models\Theme;
use App\Modules\ECommerce\Models\Store;

class ThemeService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Create default themes for tenant.
     *
     * @param  int  $tenantId
     * @return void
     */
    public function createDefaultThemes(int $tenantId): void
    {
        $themes = [
            [
                'name' => 'Modern Minimal',
                'slug' => 'modern-minimal',
                'description' => 'A modern, clean design with minimal styling',
                'is_default' => true,
                'config' => [
                    'colors' => [
                        'primary' => '#3B82F6',
                        'secondary' => '#64748B',
                        'background' => '#FFFFFF',
                        'text' => '#1E293B',
                    ],
                    'typography' => [
                        'fontFamily' => 'Inter',
                        'headingSize' => '2rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'minimal',
                            'sticky' => true,
                        ],
                        'footer' => [
                            'style' => 'simple',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Classic Shop',
                'slug' => 'classic-shop',
                'description' => 'A traditional e-commerce design',
                'is_default' => false,
                'config' => [
                    'colors' => [
                        'primary' => '#8B4513',
                        'secondary' => '#D2B48C',
                        'background' => '#FFF8DC',
                        'text' => '#654321',
                    ],
                    'typography' => [
                        'fontFamily' => 'Georgia',
                        'headingSize' => '2.5rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'classic',
                            'sticky' => false,
                        ],
                        'footer' => [
                            'style' => 'detailed',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Bold Commerce',
                'slug' => 'bold-commerce',
                'description' => 'A bold, eye-catching design',
                'is_default' => false,
                'config' => [
                    'colors' => [
                        'primary' => '#000000',
                        'secondary' => '#DC2626',
                        'background' => '#FFFFFF',
                        'text' => '#000000',
                    ],
                    'typography' => [
                        'fontFamily' => 'Arial Black',
                        'headingSize' => '3rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'bold',
                            'sticky' => true,
                        ],
                        'footer' => [
                            'style' => 'minimal',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($themes as $themeData) {
            // Check if theme already exists for this tenant
            $existingTheme = Theme::where('tenant_id', $tenantId)
                ->where('slug', $themeData['slug'])
                ->first();

            if (!$existingTheme) {
                Theme::create([
                    'tenant_id' => $tenantId,
                    'name' => $themeData['name'],
                    'slug' => $themeData['slug'],
                    'description' => $themeData['description'],
                    'is_active' => true,
                    'is_default' => $themeData['is_default'],
                    'config' => $themeData['config'],
                ]);
            }
        }
    }

    /**
     * Apply theme to store.
     *
     * @param  Store  $store
     * @param  Theme  $theme
     * @return Store
     */
    public function applyTheme(Store $store, Theme $theme): Store
    {
        $store->theme_id = $theme->id;
        $store->save();

        return $store->load('theme');
    }

    /**
     * Get theme configuration.
     *
     * @param  Theme  $theme
     * @return array
     */
    public function getThemeConfig(Theme $theme): array
    {
        return $theme->config ?? [];
    }
}

