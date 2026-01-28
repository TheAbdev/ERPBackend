<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Theme::class);

        $query = Theme::query()
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:ecommerce_themes,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'assets' => ['sometimes', 'array'],
            'preview_image' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->input('is_active', true);
        $validated['is_default'] = $request->input('is_default', false);

        if ($validated['is_default']) {
            Theme::where('tenant_id', $validated['tenant_id'])->update(['is_default' => false]);
        }

        $theme = Theme::create($validated);

        return response()->json([
            'message' => 'Theme created successfully.',
            'data' => $theme,
        ], 201);
    }

    public function show(Theme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        return response()->json([
            'data' => $theme,
        ]);
    }

    public function update(Request $request, Theme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:ecommerce_themes,slug,' . $theme->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'assets' => ['sometimes', 'array'],
            'preview_image' => ['nullable', 'string'],
        ]);

        if (array_key_exists('is_default', $validated) && $validated['is_default']) {
            Theme::where('tenant_id', $theme->tenant_id)->update(['is_default' => false]);
        }

        $theme->update($validated);

        return response()->json([
            'message' => 'Theme updated successfully.',
            'data' => $theme,
        ]);
    }

    public function destroy(Theme $theme): JsonResponse
    {
        $this->authorize('delete', $theme);

        $theme->delete();

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

        $name = $validated['name'] ?? $template['name'];
        $slug = Str::slug($name);
        $baseSlug = $slug;
        $counter = 1;
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
        ]);

        if ($theme->is_default) {
            Theme::where('tenant_id', $theme->tenant_id)
                ->where('id', '!=', $theme->id)
                ->update(['is_default' => false]);
        }

        return response()->json([
            'message' => 'Theme created from template successfully.',
            'data' => $theme,
        ], 201);
    }

    private function getTemplatePresets(): array
    {
        return [
            [
                'template_slug' => 'modern-minimal',
                'name' => 'Modern Minimal',
                'description' => 'Clean layout with calm tones and generous spacing.',
                'config' => [
                    'colors' => [
                        'primary' => '#2563EB',
                        'secondary' => '#64748B',
                        'background' => '#FFFFFF',
                        'text' => '#1F2937',
                    ],
                    'typography' => [
                        'fontFamily' => 'Inter, Arial, sans-serif',
                        'headingSize' => '2.5rem',
                    ],
                    'layout' => [
                        'radius' => '12px',
                        'spacing' => '24px',
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
                'config' => [
                    'colors' => [
                        'primary' => '#DC2626',
                        'secondary' => '#111827',
                        'background' => '#F8FAFC',
                        'text' => '#111827',
                    ],
                    'typography' => [
                        'fontFamily' => 'Poppins, Arial, sans-serif',
                        'headingSize' => '2.75rem',
                    ],
                ],
                        ],
            [
                'template_slug' => 'soft-pastel',
                'name' => 'Soft Pastel',
                'description' => 'Gentle pastels for lifestyle brands.',
                'config' => [
                    'colors' => [
                        'primary' => '#A855F7',
                        'secondary' => '#F59E0B',
                        'background' => '#FFF7ED',
                        'text' => '#374151',
                    ],
                    'typography' => [
                        'fontFamily' => 'Nunito, Arial, sans-serif',
                        'headingSize' => '2.4rem',
                    ],
                ],
            ],
            [
                'template_slug' => 'dark-lux',
                'name' => 'Dark Lux',
                'description' => 'Premium dark theme with gold accents.',
                'config' => [
                    'colors' => [
                        'primary' => '#F59E0B',
                        'secondary' => '#9CA3AF',
                        'background' => '#111827',
                        'text' => '#F9FAFB',
                    ],
                    'typography' => [
                        'fontFamily' => 'Playfair Display, serif',
                        'headingSize' => '2.8rem',
                    ],
                ],
                        ],
            [
                'template_slug' => 'fresh-market',
                'name' => 'Fresh Market',
                'description' => 'Bright, natural palette for grocery brands.',
                'config' => [
                    'colors' => [
                        'primary' => '#16A34A',
                        'secondary' => '#4B5563',
                        'background' => '#F0FDF4',
                        'text' => '#1F2937',
                    ],
                    'typography' => [
                        'fontFamily' => 'Source Sans Pro, Arial, sans-serif',
                        'headingSize' => '2.4rem',
                    ],
                ],
            ],
            [
                'template_slug' => 'tech-store',
                'name' => 'Tech Store',
                'description' => 'Cool tones for electronics and gadgets.',
                'config' => [
                    'colors' => [
                        'primary' => '#0EA5E9',
                        'secondary' => '#334155',
                        'background' => '#F8FAFC',
                        'text' => '#0F172A',
                    ],
                    'typography' => [
                        'fontFamily' => 'Roboto, Arial, sans-serif',
                        'headingSize' => '2.6rem',
                    ],
                ],
            ],
            [
                'template_slug' => 'artisan-craft',
                'name' => 'Artisan Craft',
                'description' => 'Warm handcrafted feel for boutique brands.',
                'config' => [
                    'colors' => [
                        'primary' => '#B45309',
                        'secondary' => '#6B7280',
                        'background' => '#FFFBEB',
                        'text' => '#3F2D20',
                    ],
                    'typography' => [
                        'fontFamily' => 'Merriweather, serif',
                        'headingSize' => '2.5rem',
                    ],
                ],
                        ],
            [
                'template_slug' => 'clean-white',
                'name' => 'Clean White',
                'description' => 'Minimal white-first storefront.',
                'config' => [
                    'colors' => [
                        'primary' => '#1D4ED8',
                        'secondary' => '#94A3B8',
                        'background' => '#FFFFFF',
                        'text' => '#0F172A',
                    ],
                    'typography' => [
                        'fontFamily' => 'Helvetica, Arial, sans-serif',
                        'headingSize' => '2.3rem',
                    ],
                ],
            ],
            [
                'template_slug' => 'vibrant-pop',
                'name' => 'Vibrant Pop',
                'description' => 'Colorful UI for youth-focused brands.',
                'config' => [
                    'colors' => [
                        'primary' => '#EC4899',
                        'secondary' => '#6366F1',
                        'background' => '#FDF2F8',
                        'text' => '#312E81',
                    ],
                    'typography' => [
                        'fontFamily' => 'Rubik, Arial, sans-serif',
                        'headingSize' => '2.6rem',
                    ],
                ],
                        ],
            [
                'template_slug' => 'classic-retail',
                'name' => 'Classic Retail',
                'description' => 'Balanced layout for everyday retail.',
                'config' => [
                    'colors' => [
                        'primary' => '#0F766E',
                        'secondary' => '#64748B',
                        'background' => '#F8FAFC',
                        'text' => '#0F172A',
                        ],
                    'typography' => [
                        'fontFamily' => 'Lato, Arial, sans-serif',
                        'headingSize' => '2.4rem',
                    ],
                ],
            ],
        ];
    }
}
