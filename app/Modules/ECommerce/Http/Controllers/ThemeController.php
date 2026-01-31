<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Theme;
use App\Modules\ECommerce\Models\ThemePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Theme::class);

        $query = Theme::query()
            ->with('pages')
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $themes = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $themes->items(),
            'meta' => [
                'current_page' => $themes->currentPage(),
                'per_page' => $themes->perPage(),
                'total' => $themes->total(),
                'last_page' => $themes->lastPage(),
            ],
        ]);
    }

    public function templates(): JsonResponse
    {
        return response()->json([
            'data' => $this->getTemplatePresets(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Theme::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'assets' => ['sometimes', 'array'],
            'preview_image' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        
        // Generate unique slug by appending tenant_id
        $baseSlug = Str::slug($validated['slug'] ?? $validated['name']) . '-' . $validated['tenant_id'];
        $slug = $baseSlug;
        $counter = 1;
        while (Theme::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;
        
        $validated['is_active'] = $request->input('is_active', true);
        $validated['is_default'] = $request->input('is_default', false);

        if ($validated['is_default']) {
            Theme::where('tenant_id', $validated['tenant_id'])->update(['is_default' => false]);
        }

        $theme = Theme::create($validated);

        // Create empty pages for all page types
        $this->createEmptyPages($theme);

        return response()->json([
            'message' => 'Theme created successfully.',
            'data' => $theme->load('pages'),
        ], 201);
    }

    public function show(Request $request, int $theme): JsonResponse
    {
        $themeModel = Theme::where('tenant_id', $request->user()->tenant_id)
            ->with('pages')
            ->find($theme);

        if (!$themeModel) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        $this->authorize('view', $themeModel);

        return response()->json([
            'data' => $themeModel,
        ]);
    }

    public function update(Request $request, int $theme): JsonResponse
    {
        $themeModel = Theme::where('tenant_id', $request->user()->tenant_id)->find($theme);

        if (!$themeModel) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        $this->authorize('update', $themeModel);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:ecommerce_themes,slug,' . $themeModel->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'assets' => ['sometimes', 'array'],
            'preview_image' => ['nullable', 'string'],
        ]);

        if (array_key_exists('is_default', $validated) && $validated['is_default']) {
            Theme::where('tenant_id', $themeModel->tenant_id)->update(['is_default' => false]);
        }

        $themeModel->update($validated);

        return response()->json([
            'message' => 'Theme updated successfully.',
            'data' => $themeModel->load('pages'),
        ]);
    }

    public function destroy(Request $request, int $theme): JsonResponse
    {
        $themeModel = Theme::where('tenant_id', $request->user()->tenant_id)->find($theme);

        if (!$themeModel) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        $this->authorize('delete', $themeModel);

        $themeModel->delete();

        return response()->json([
            'message' => 'Theme deleted successfully.',
        ]);
    }

    public function createFromTemplate(Request $request): JsonResponse
    {
        $this->authorize('create', Theme::class);

        $validated = $request->validate([
            'template_slug' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $template = collect($this->getTemplatePresets())
            ->firstWhere('template_slug', $validated['template_slug']);

        if (!$template) {
            return response()->json(['message' => 'Template not found.'], 404);
        }

        $tenantId = $request->user()->tenant_id;
        $name = $validated['name'] ?? $template['name'];
        
        // Generate unique slug by appending tenant_id to avoid global conflicts
        $baseSlug = Str::slug($name) . '-' . $tenantId;
        $slug = $baseSlug;
        $counter = 1;
        
        // Check for uniqueness globally (due to database constraint)
        while (Theme::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $theme = Theme::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $name,
            'slug' => $slug,
            'description' => $template['description'] ?? null,
            'config' => $template['config'] ?? [],
            'is_active' => true,
            'is_default' => $validated['is_default'] ?? false,
            'source_template' => $validated['template_slug'],
        ]);

        if ($theme->is_default) {
            Theme::where('tenant_id', $theme->tenant_id)
                ->where('id', '!=', $theme->id)
                ->update(['is_default' => false]);
        }

        // Create pages from template
        $this->createPagesFromTemplate($theme, $template);

        return response()->json([
            'message' => 'Theme created from template successfully.',
            'data' => $theme->load('pages'),
        ], 201);
    }

    /**
     * Get all pages for a theme.
     */
    public function getPages(Request $request, int $theme): JsonResponse
    {
        $themeModel = Theme::where('tenant_id', $request->user()->tenant_id)
            ->with('pages')
            ->find($theme);

        if (!$themeModel) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        $this->authorize('view', $themeModel);

        return response()->json([
            'data' => $themeModel->pages,
        ]);
    }

    /**
     * Get a specific page by type.
     */
    public function getPage(Request $request, int $theme, string $pageType): JsonResponse
    {
        $themeModel = Theme::where('tenant_id', $request->user()->tenant_id)->find($theme);

        if (!$themeModel) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        $this->authorize('view', $themeModel);

        if (!in_array($pageType, ThemePage::PAGE_TYPES)) {
            return response()->json(['message' => 'Invalid page type.'], 400);
        }

        $page = $themeModel->getPage($pageType);

        if (!$page) {
            // Create the page if it doesn't exist
            $page = ThemePage::create([
                'theme_id' => $themeModel->id,
                'page_type' => $pageType,
                'title' => ucfirst($pageType),
                'content' => ['blocks' => []],
                'is_published' => false,
            ]);
        }

        return response()->json([
            'data' => $page,
        ]);
    }

    /**
     * Update a theme page (save as draft).
     */
    public function updatePage(Request $request, int $theme, string $pageType): JsonResponse
    {
        $themeModel = Theme::where('tenant_id', $request->user()->tenant_id)->find($theme);

        if (!$themeModel) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        $this->authorize('update', $themeModel);

        if (!in_array($pageType, ThemePage::PAGE_TYPES)) {
            return response()->json(['message' => 'Invalid page type.'], 400);
        }

        $validated = $request->validate([
            'content' => ['required', 'array'],
            'title' => ['sometimes', 'string', 'max:255'],
        ]);

        $page = $themeModel->getPage($pageType);

        if (!$page) {
            $page = ThemePage::create([
                'theme_id' => $themeModel->id,
                'page_type' => $pageType,
                'title' => $validated['title'] ?? ucfirst($pageType),
                'draft_content' => $validated['content'],
                'is_published' => false,
            ]);
        } else {
            $page->draft_content = $validated['content'];
            if (isset($validated['title'])) {
                $page->title = $validated['title'];
            }
            $page->save();
        }

        return response()->json([
            'message' => 'Page saved as draft.',
            'data' => $page,
        ]);
    }

    /**
     * Publish a theme page.
     */
    public function publishPage(Request $request, int $theme, string $pageType): JsonResponse
    {
        $themeModel = Theme::where('tenant_id', $request->user()->tenant_id)->find($theme);

        if (!$themeModel) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        $this->authorize('update', $themeModel);

        if (!in_array($pageType, ThemePage::PAGE_TYPES)) {
            return response()->json(['message' => 'Invalid page type.'], 400);
        }

        $page = $themeModel->getPage($pageType);

        if (!$page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        // If content is provided in request, use it directly
        $validated = $request->validate([
            'content' => ['sometimes', 'array'],
        ]);

        if (isset($validated['content'])) {
            $page->content = $validated['content'];
            $page->draft_content = null;
        } else if ($page->draft_content) {
            $page->content = $page->draft_content;
            $page->draft_content = null;
        }

        $page->is_published = true;
        $page->published_at = now();
        $page->save();

        return response()->json([
            'message' => 'Page published successfully.',
            'data' => $page,
        ]);
    }

    /**
     * Create empty pages for a new theme.
     */
    private function createEmptyPages(Theme $theme): void
    {
        foreach (ThemePage::PAGE_TYPES as $pageType) {
            ThemePage::create([
                'theme_id' => $theme->id,
                'page_type' => $pageType,
                'title' => ucfirst($pageType),
                'content' => ['blocks' => []],
                'is_published' => false,
            ]);
        }
    }

    /**
     * Create pages from a template.
     */
    private function createPagesFromTemplate(Theme $theme, array $template): void
    {
        $pageTemplates = $template['pages'] ?? [];

        foreach (ThemePage::PAGE_TYPES as $pageType) {
            $pageContent = $pageTemplates[$pageType] ?? ['blocks' => $this->getDefaultPageBlocks($pageType, $template)];
            
            ThemePage::create([
                'theme_id' => $theme->id,
                'page_type' => $pageType,
                'title' => ucfirst($pageType),
                'content' => $pageContent,
                'is_published' => true,
                'published_at' => now(),
            ]);
        }
    }

    /**
     * Get default blocks for a page type based on template.
     */
    private function getDefaultPageBlocks(string $pageType, array $template): array
    {
        $colors = $template['config']['colors'] ?? [
            'primary' => '#2563EB',
            'secondary' => '#64748B',
            'background' => '#FFFFFF',
            'text' => '#1F2937',
        ];

        switch ($pageType) {
            case 'home':
                return $this->getHomePageBlocks($colors, $template);
            case 'products':
                return $this->getProductsPageBlocks($colors, $template);
            case 'product':
                return $this->getProductDetailBlocks($colors, $template);
            case 'cart':
                return $this->getCartPageBlocks($colors, $template);
            case 'checkout':
                return $this->getCheckoutPageBlocks($colors, $template);
            case 'account':
                return $this->getAccountPageBlocks($colors, $template);
            default:
                return [];
        }
    }

    private function getHomePageBlocks(array $colors, array $template): array
    {
        return [
            [
                'id' => 'hero-' . Str::random(8),
                'type' => 'hero',
                'props' => [
                    'title' => 'Welcome to Our Store',
                    'subtitle' => 'Discover amazing products at great prices',
                    'buttonText' => 'Shop Now',
                    'buttonLink' => '/products',
                    'backgroundColor' => $colors['primary'],
                    'textColor' => '#FFFFFF',
                    'alignment' => 'center',
                    'height' => 'large',
                ],
            ],
            [
                'id' => 'featured-' . Str::random(8),
                'type' => 'featured-products',
                'props' => [
                    'title' => 'Featured Products',
                    'subtitle' => 'Check out our most popular items',
                    'limit' => 8,
                    'columns' => 4,
                    'showPrice' => true,
                    'showAddToCart' => true,
                ],
            ],
            [
                'id' => 'banner-' . Str::random(8),
                'type' => 'banner',
                'props' => [
                    'title' => 'Special Offer',
                    'description' => 'Get 20% off on your first order',
                    'buttonText' => 'Learn More',
                    'buttonLink' => '/products',
                    'backgroundColor' => $colors['secondary'],
                    'textColor' => '#FFFFFF',
                ],
            ],
            [
                'id' => 'categories-' . Str::random(8),
                'type' => 'categories',
                'props' => [
                    'title' => 'Shop by Category',
                    'columns' => 3,
                    'showImage' => true,
                ],
            ],
        ];
    }

    private function getProductsPageBlocks(array $colors, array $template): array
    {
        return [
            [
                'id' => 'header-' . Str::random(8),
                'type' => 'page-header',
                'props' => [
                    'title' => 'Our Products',
                    'subtitle' => 'Browse our collection',
                    'backgroundColor' => $colors['background'],
                    'textColor' => $colors['text'],
                ],
            ],
            [
                'id' => 'filters-' . Str::random(8),
                'type' => 'product-filters',
                'props' => [
                    'showCategories' => true,
                    'showPriceRange' => true,
                    'showSort' => true,
                ],
            ],
            [
                'id' => 'grid-' . Str::random(8),
                'type' => 'product-grid',
                'props' => [
                    'columns' => 4,
                    'showPrice' => true,
                    'showAddToCart' => true,
                    'showQuickView' => true,
                    'pagination' => true,
                    'itemsPerPage' => 12,
                ],
            ],
        ];
    }

    private function getProductDetailBlocks(array $colors, array $template): array
    {
        return [
            [
                'id' => 'product-' . Str::random(8),
                'type' => 'product-detail',
                'props' => [
                    'showGallery' => true,
                    'showPrice' => true,
                    'showDescription' => true,
                    'showAddToCart' => true,
                    'showQuantity' => true,
                    'showStock' => true,
                    'galleryPosition' => 'left',
                ],
            ],
            [
                'id' => 'tabs-' . Str::random(8),
                'type' => 'product-tabs',
                'props' => [
                    'tabs' => ['description', 'specifications', 'reviews'],
                ],
            ],
            [
                'id' => 'related-' . Str::random(8),
                'type' => 'related-products',
                'props' => [
                    'title' => 'You May Also Like',
                    'limit' => 4,
                    'columns' => 4,
                ],
            ],
        ];
    }

    private function getCartPageBlocks(array $colors, array $template): array
    {
        return [
            [
                'id' => 'header-' . Str::random(8),
                'type' => 'page-header',
                'props' => [
                    'title' => 'Shopping Cart',
                    'subtitle' => 'Review your items',
                    'backgroundColor' => $colors['background'],
                    'textColor' => $colors['text'],
                ],
            ],
            [
                'id' => 'cart-' . Str::random(8),
                'type' => 'cart-items',
                'props' => [
                    'showImage' => true,
                    'showPrice' => true,
                    'showQuantity' => true,
                    'showRemove' => true,
                    'showSubtotal' => true,
                ],
            ],
            [
                'id' => 'summary-' . Str::random(8),
                'type' => 'cart-summary',
                'props' => [
                    'showSubtotal' => true,
                    'showTax' => true,
                    'showShipping' => true,
                    'showTotal' => true,
                    'checkoutButtonText' => 'Proceed to Checkout',
                ],
            ],
        ];
    }

    private function getCheckoutPageBlocks(array $colors, array $template): array
    {
        return [
            [
                'id' => 'header-' . Str::random(8),
                'type' => 'page-header',
                'props' => [
                    'title' => 'Checkout',
                    'subtitle' => 'Complete your order',
                    'backgroundColor' => $colors['background'],
                    'textColor' => $colors['text'],
                ],
            ],
            [
                'id' => 'checkout-' . Str::random(8),
                'type' => 'checkout-form',
                'props' => [
                    'showBillingAddress' => true,
                    'showShippingAddress' => true,
                    'showPaymentMethods' => true,
                    'showOrderSummary' => true,
                ],
            ],
        ];
    }

    private function getAccountPageBlocks(array $colors, array $template): array
    {
        return [
            [
                'id' => 'header-' . Str::random(8),
                'type' => 'page-header',
                'props' => [
                    'title' => 'My Account',
                    'subtitle' => 'Manage your orders and profile',
                    'backgroundColor' => $colors['background'],
                    'textColor' => $colors['text'],
                ],
            ],
            [
                'id' => 'orders-' . Str::random(8),
                'type' => 'order-history',
                'props' => [
                    'title' => 'Order History',
                    'showDate' => true,
                    'showStatus' => true,
                    'showTotal' => true,
                    'showDetails' => true,
                    'itemsPerPage' => 10,
                ],
            ],
        ];
    }

    private function getTemplatePresets(): array
    {
        return [
            [
                'template_slug' => 'modern-minimal',
                'name' => 'Modern Minimal',
                'description' => 'Clean layout with calm tones and generous spacing.',
                'preview_image' => '/templates/modern-minimal.png',
                'config' => [
                    'colors' => [
                        'primary' => '#2563EB',
                        'secondary' => '#64748B',
                        'background' => '#FFFFFF',
                        'text' => '#1F2937',
                        'accent' => '#3B82F6',
                    ],
                    'typography' => [
                        'fontFamily' => 'Inter, Arial, sans-serif',
                        'headingSize' => '2.5rem',
                        'bodySize' => '1rem',
                    ],
                    'layout' => [
                        'radius' => '12px',
                        'spacing' => '24px',
                        'maxWidth' => '1280px',
                    ],
                    'translations' => [
                        'en' => [
                            'home' => 'Home',
                            'products' => 'Products',
                            'cart' => 'Cart',
                            'my_orders' => 'My Orders',
                        ],
                        'ar' => [
                            'home' => 'الرئيسية',
                            'products' => 'المنتجات',
                            'cart' => 'السلة',
                            'my_orders' => 'طلباتي',
                        ],
                    ],
                ],
            ],
            [
                'template_slug' => 'bold-commerce',
                'name' => 'Bold Commerce',
                'description' => 'High-contrast palette for energetic storefronts.',
                'preview_image' => '/templates/bold-commerce.png',
                'config' => [
                    'colors' => [
                        'primary' => '#DC2626',
                        'secondary' => '#111827',
                        'background' => '#F8FAFC',
                        'text' => '#111827',
                        'accent' => '#EF4444',
                    ],
                    'typography' => [
                        'fontFamily' => 'Poppins, Arial, sans-serif',
                        'headingSize' => '2.75rem',
                        'bodySize' => '1rem',
                    ],
                    'layout' => [
                        'radius' => '8px',
                        'spacing' => '20px',
                        'maxWidth' => '1400px',
                    ],
                ],
            ],
            [
                'template_slug' => 'soft-pastel',
                'name' => 'Soft Pastel',
                'description' => 'Gentle pastels for lifestyle brands.',
                'preview_image' => '/templates/soft-pastel.png',
                'config' => [
                    'colors' => [
                        'primary' => '#A855F7',
                        'secondary' => '#F59E0B',
                        'background' => '#FFF7ED',
                        'text' => '#374151',
                        'accent' => '#C084FC',
                    ],
                    'typography' => [
                        'fontFamily' => 'Nunito, Arial, sans-serif',
                        'headingSize' => '2.4rem',
                        'bodySize' => '1rem',
                    ],
                    'layout' => [
                        'radius' => '16px',
                        'spacing' => '28px',
                        'maxWidth' => '1200px',
                    ],
                ],
            ],
            [
                'template_slug' => 'dark-lux',
                'name' => 'Dark Lux',
                'description' => 'Premium dark theme with gold accents.',
                'preview_image' => '/templates/dark-lux.png',
                'config' => [
                    'colors' => [
                        'primary' => '#F59E0B',
                        'secondary' => '#9CA3AF',
                        'background' => '#111827',
                        'text' => '#F9FAFB',
                        'accent' => '#FBBF24',
                    ],
                    'typography' => [
                        'fontFamily' => 'Playfair Display, serif',
                        'headingSize' => '2.8rem',
                        'bodySize' => '1.1rem',
                    ],
                    'layout' => [
                        'radius' => '4px',
                        'spacing' => '32px',
                        'maxWidth' => '1320px',
                    ],
                ],
            ],
            [
                'template_slug' => 'fresh-market',
                'name' => 'Fresh Market',
                'description' => 'Bright, natural palette for grocery brands.',
                'preview_image' => '/templates/fresh-market.png',
                'config' => [
                    'colors' => [
                        'primary' => '#16A34A',
                        'secondary' => '#4B5563',
                        'background' => '#F0FDF4',
                        'text' => '#1F2937',
                        'accent' => '#22C55E',
                    ],
                    'typography' => [
                        'fontFamily' => 'Source Sans Pro, Arial, sans-serif',
                        'headingSize' => '2.4rem',
                        'bodySize' => '1rem',
                    ],
                    'layout' => [
                        'radius' => '10px',
                        'spacing' => '22px',
                        'maxWidth' => '1280px',
                    ],
                ],
            ],
            [
                'template_slug' => 'tech-store',
                'name' => 'Tech Store',
                'description' => 'Cool tones for electronics and gadgets.',
                'preview_image' => '/templates/tech-store.png',
                'config' => [
                    'colors' => [
                        'primary' => '#0EA5E9',
                        'secondary' => '#334155',
                        'background' => '#F8FAFC',
                        'text' => '#0F172A',
                        'accent' => '#38BDF8',
                    ],
                    'typography' => [
                        'fontFamily' => 'Roboto, Arial, sans-serif',
                        'headingSize' => '2.6rem',
                        'bodySize' => '0.95rem',
                    ],
                    'layout' => [
                        'radius' => '6px',
                        'spacing' => '18px',
                        'maxWidth' => '1440px',
                    ],
                ],
            ],
            [
                'template_slug' => 'artisan-craft',
                'name' => 'Artisan Craft',
                'description' => 'Warm handcrafted feel for boutique brands.',
                'preview_image' => '/templates/artisan-craft.png',
                'config' => [
                    'colors' => [
                        'primary' => '#B45309',
                        'secondary' => '#6B7280',
                        'background' => '#FFFBEB',
                        'text' => '#3F2D20',
                        'accent' => '#D97706',
                    ],
                    'typography' => [
                        'fontFamily' => 'Merriweather, serif',
                        'headingSize' => '2.5rem',
                        'bodySize' => '1.05rem',
                    ],
                    'layout' => [
                        'radius' => '2px',
                        'spacing' => '26px',
                        'maxWidth' => '1180px',
                    ],
                ],
            ],
            [
                'template_slug' => 'clean-white',
                'name' => 'Clean White',
                'description' => 'Minimal white-first storefront.',
                'preview_image' => '/templates/clean-white.png',
                'config' => [
                    'colors' => [
                        'primary' => '#1D4ED8',
                        'secondary' => '#94A3B8',
                        'background' => '#FFFFFF',
                        'text' => '#0F172A',
                        'accent' => '#2563EB',
                    ],
                    'typography' => [
                        'fontFamily' => 'Helvetica, Arial, sans-serif',
                        'headingSize' => '2.3rem',
                        'bodySize' => '1rem',
                    ],
                    'layout' => [
                        'radius' => '0px',
                        'spacing' => '24px',
                        'maxWidth' => '1200px',
                    ],
                ],
            ],
            [
                'template_slug' => 'vibrant-pop',
                'name' => 'Vibrant Pop',
                'description' => 'Colorful UI for youth-focused brands.',
                'preview_image' => '/templates/vibrant-pop.png',
                'config' => [
                    'colors' => [
                        'primary' => '#EC4899',
                        'secondary' => '#6366F1',
                        'background' => '#FDF2F8',
                        'text' => '#312E81',
                        'accent' => '#F472B6',
                    ],
                    'typography' => [
                        'fontFamily' => 'Rubik, Arial, sans-serif',
                        'headingSize' => '2.6rem',
                        'bodySize' => '1rem',
                    ],
                    'layout' => [
                        'radius' => '20px',
                        'spacing' => '20px',
                        'maxWidth' => '1300px',
                    ],
                ],
            ],
            [
                'template_slug' => 'classic-retail',
                'name' => 'Classic Retail',
                'description' => 'Balanced layout for everyday retail.',
                'preview_image' => '/templates/classic-retail.png',
                'config' => [
                    'colors' => [
                        'primary' => '#0F766E',
                        'secondary' => '#64748B',
                        'background' => '#F8FAFC',
                        'text' => '#0F172A',
                        'accent' => '#14B8A6',
                    ],
                    'typography' => [
                        'fontFamily' => 'Lato, Arial, sans-serif',
                        'headingSize' => '2.4rem',
                        'bodySize' => '1rem',
                    ],
                    'layout' => [
                        'radius' => '8px',
                        'spacing' => '24px',
                        'maxWidth' => '1280px',
                    ],
                ],
            ],
        ];
    }
}
