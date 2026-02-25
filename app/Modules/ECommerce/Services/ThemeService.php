<?php

namespace App\Modules\ECommerce\Services;

use App\Core\Services\TenantContext;
use App\Modules\ECommerce\Models\Theme;
use App\Modules\ECommerce\Models\ThemePage;
use App\Modules\ECommerce\Models\Store;

class ThemeService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Built-in theme slugs (match frontend StoreThemeRegistry).
     */
    public const BUILTIN_THEME_SLUGS = [
        'atlas-store',
        'echo-store',
        'bloom-store',
        'northwind-store',
        'forge-store',
        'horizon-store',
        'copper-store',
        'drift-store',
        'lumen-store',
        'aurora-store',
    ];

    /**
     * Default blocks content for each page type.
     */
    protected function defaultBlocksForPageType(string $pageType): array
    {
        $blocks = [
            'home' => [
                ['type' => 'header', 'content' => [], 'settings' => []],
                ['type' => 'hero', 'content' => ['title' => 'Welcome to Our Store', 'subtitle' => 'Discover great products', 'buttonText' => 'Shop Now', 'buttonLink' => '#products', 'imageUrl' => ''], 'settings' => []],
                ['type' => 'featured-products', 'content' => ['title' => 'Featured Products', 'subtitle' => 'Check out our picks', 'limit' => 8], 'settings' => []],
                ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
            ],
            'products' => [
                ['type' => 'header', 'content' => [], 'settings' => []],
                ['type' => 'page-header', 'content' => ['title' => 'Products', 'subtitle' => 'Browse our collection'], 'settings' => []],
                ['type' => 'product-filters', 'content' => [], 'settings' => []],
                ['type' => 'products_grid', 'content' => ['title' => 'All Products'], 'settings' => []],
                ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
            ],
            'product' => [
                ['type' => 'header', 'content' => [], 'settings' => []],
                ['type' => 'product-detail', 'content' => [], 'settings' => []],
                ['type' => 'related-products', 'content' => ['title' => 'Related Products', 'limit' => 4], 'settings' => []],
                ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
            ],
            'cart' => [
                ['type' => 'header', 'content' => [], 'settings' => []],
                ['type' => 'cart-items', 'content' => ['title' => 'Shopping Cart'], 'settings' => []],
                ['type' => 'cart-summary', 'content' => ['title' => 'Order Summary'], 'settings' => []],
                ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
            ],
            'checkout' => [
                ['type' => 'header', 'content' => [], 'settings' => []],
                ['type' => 'checkout-form', 'content' => [], 'settings' => []],
                ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
            ],
            'account' => [
                ['type' => 'header', 'content' => [], 'settings' => []],
                ['type' => 'order-history', 'content' => ['title' => 'My Orders'], 'settings' => []],
                ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []],
            ],
        ];

        return $blocks[$pageType] ?? [['type' => 'header', 'content' => [], 'settings' => []], ['type' => 'footer', 'content' => ['text' => '© All rights reserved.'], 'settings' => []]];
    }

    /**
     * Create default themes for tenant (10 built-in themes + theme_pages).
     *
     * @param  int  $tenantId
     * @return void
     */
    public function createDefaultThemes(int $tenantId): void
    {
        $themesConfig = [
            ['name' => 'Atlas Store', 'slug' => 'atlas-store', 'description' => 'Professional store layout with accent bar', 'is_default' => true, 'primary' => '#1E40AF', 'secondary' => '#64748B'],
            ['name' => 'Echo Store', 'slug' => 'echo-store', 'description' => 'Education-style two-row layout', 'is_default' => false, 'primary' => '#7C3AED', 'secondary' => '#A78BFA'],
            ['name' => 'Bloom Store', 'slug' => 'bloom-store', 'description' => 'Wellness-inspired soft design', 'is_default' => false, 'primary' => '#059669', 'secondary' => '#34D399'],
            ['name' => 'Northwind Store', 'slug' => 'northwind-store', 'description' => 'Brand showcase centered layout', 'is_default' => false, 'primary' => '#4C0519', 'secondary' => '#FDA4AF'],
            ['name' => 'Forge Store', 'slug' => 'forge-store', 'description' => 'Industrial bold layout', 'is_default' => false, 'primary' => '#1E293B', 'secondary' => '#F59E0B'],
            ['name' => 'Horizon Store', 'slug' => 'horizon-store', 'description' => 'Real estate style vertical accent', 'is_default' => false, 'primary' => '#0F766E', 'secondary' => '#5EEAD4'],
            ['name' => 'Copper Store', 'slug' => 'copper-store', 'description' => 'Agency tab-style layout', 'is_default' => false, 'primary' => '#B45309', 'secondary' => '#FCD34D'],
            ['name' => 'Drift Store', 'slug' => 'drift-store', 'description' => 'Minimal compact layout', 'is_default' => false, 'primary' => '#6366F1', 'secondary' => '#A5B4FC'],
            ['name' => 'Lumen Store', 'slug' => 'lumen-store', 'description' => 'Tech lab dot-accent layout', 'is_default' => false, 'primary' => '#2563EB', 'secondary' => '#93C5FD'],
            ['name' => 'Aurora Store', 'slug' => 'aurora-store', 'description' => 'Creative gradient layout', 'is_default' => false, 'primary' => '#0F172A', 'secondary' => '#F97316'],
        ];

        foreach ($themesConfig as $index => $themeData) {
            $existingTheme = Theme::where('tenant_id', $tenantId)
                ->where('slug', $themeData['slug'])
                ->first();

            if (!$existingTheme) {
                $theme = Theme::create([
                    'tenant_id' => $tenantId,
                    'name' => $themeData['name'],
                    'slug' => $themeData['slug'],
                    'description' => $themeData['description'],
                    'is_active' => true,
                    'is_default' => $themeData['is_default'],
                    'config' => [
                        'colors' => [
                            'primary' => $themeData['primary'],
                            'secondary' => $themeData['secondary'],
                            'background' => '#FFFFFF',
                            'text' => '#1E293B',
                        ],
                        'typography' => [
                            'fontFamily' => 'Inter',
                            'headingSize' => '2rem',
                        ],
                    ],
                ]);
                $this->createDefaultThemePages($theme->id);
            }
        }
    }

    /**
     * Create default theme pages for a theme (home, products, product, cart, checkout, account).
     *
     * @param  int  $themeId
     * @return void
     */
    public function createDefaultThemePages(int $themeId): void
    {
        $pageTypes = ['home', 'products', 'product', 'cart', 'checkout', 'account'];
        $titles = ['Home', 'Products', 'Product Details', 'Cart', 'Checkout', 'My Orders'];

        foreach ($pageTypes as $i => $pageType) {
            $exists = ThemePage::where('theme_id', $themeId)->where('page_type', $pageType)->exists();
            if (!$exists) {
                ThemePage::create([
                    'theme_id' => $themeId,
                    'page_type' => $pageType,
                    'title' => $titles[$i],
                    'content' => ['blocks' => $this->defaultBlocksForPageType($pageType)],
                    'is_published' => true,
                    'published_at' => now(),
                ]);
            }
        }
    }

    /**
     * Apply theme to store.
     * Preserves page content (blocks): copies current theme's pages content to the new theme
     * so only the visual theme changes, not the content (like website template behaviour).
     *
     * @param  Store  $store
     * @param  Theme  $theme
     * @return Store
     */
    public function applyTheme(Store $store, Theme $theme): Store
    {
        $oldThemeId = $store->theme_id;
        $newTheme = $theme;

        if ($oldThemeId && (int) $oldThemeId !== (int) $newTheme->id) {
            $oldTheme = Theme::find($oldThemeId);
            if ($oldTheme) {
                $this->copyThemePagesContentToTheme($oldTheme, $newTheme);
            }
        }

        $store->theme_id = $newTheme->id;
        $store->save();

        return $store->load('theme');
    }

    /**
     * Copy each page's content (blocks) from source theme to target theme.
     * So when switching theme, the same blocks remain; only the theme style changes.
     */
    protected function copyThemePagesContentToTheme(Theme $sourceTheme, Theme $targetTheme): void
    {
        $pageTypes = ['home', 'products', 'product', 'cart', 'checkout', 'account'];
        $titles = ['Home', 'Products', 'Product Details', 'Cart', 'Checkout', 'My Orders'];

        foreach ($pageTypes as $i => $pageType) {
            $sourcePage = $sourceTheme->getPage($pageType);
            if (!$sourcePage) {
                continue;
            }

            $content = $sourcePage->getDisplayContent(true);
            $blocks = $content['blocks'] ?? [];
            $contentToCopy = ['blocks' => $blocks];

            $targetPage = $targetTheme->getPage($pageType);
            if ($targetPage) {
                $targetPage->content = $contentToCopy;
                $targetPage->draft_content = null;
                $targetPage->save();
            } else {
                ThemePage::create([
                    'theme_id' => $targetTheme->id,
                    'page_type' => $pageType,
                    'title' => $titles[$i] ?? ucfirst($pageType),
                    'content' => $contentToCopy,
                    'is_published' => $sourcePage->is_published,
                    'published_at' => $sourcePage->published_at,
                ]);
            }
        }
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

