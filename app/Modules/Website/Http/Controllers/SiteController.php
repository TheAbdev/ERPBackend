<?php

namespace App\Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Services\TenantContext;
use App\Modules\Website\Models\WebsitePage;
use App\Modules\Website\Models\WebsiteSite;
use App\Modules\Website\Models\WebsiteTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    /**
     * List sites for tenant.
     * Each tenant can only have one site.
     */
    public function index(): JsonResponse
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        
        if (!$tenantId) {
            return response()->json([
                'data' => null,
            ]);
        }

        $site = WebsiteSite::with('template', 'pages')
            ->where('tenant_id', $tenantId)
            ->first();

        return response()->json([
            'data' => $site,
        ]);
    }

    /**
     * Get current site for tenant.
     */
    public function getCurrentSite(): JsonResponse
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        
        if (!$tenantId) {
            return response()->json([
                'data' => null,
            ]);
        }

        $site = WebsiteSite::with('template', 'pages')
            ->where('tenant_id', $tenantId)
            ->first();

        return response()->json([
            'data' => $site,
        ]);
    }

    /**
     * Create a new website site.
     * Each tenant can only have one site.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant context is required.',
            ], 400);
        }

        // Check if tenant already has a site
        $existingSite = WebsiteSite::where('tenant_id', $tenantId)->first();
        if ($existingSite) {
            return response()->json([
                'message' => 'Tenant can only have one website site. Please update the existing site instead.',
                'data' => $existingSite->load('template', 'pages'),
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:website_templates,id'],
            'settings' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        $site = WebsiteSite::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'slug' => $slug,
            'domain' => $validated['domain'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'template_id' => $validated['template_id'] ?? null,
            'settings' => $validated['settings'] ?? null,
        ]);

        $this->applyTemplateSettings($site);
        $this->createDefaultPagesFromTemplate($site);

        return response()->json([
            'message' => 'Website site created successfully.',
            'data' => $site->load('template', 'pages'),
        ], 201);
    }

    /**
     * Show a specific site.
     */
    public function show(WebsiteSite $site): JsonResponse
    {
        return response()->json([
            'data' => $site->load('template', 'pages'),
        ]);
    }

    /**
     * Update a site.
     */
    public function update(Request $request, WebsiteSite $site): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:website_templates,id'],
            'settings' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $oldTemplateId = $site->template_id;
        $site->update($validated);

        // If template_id changed, apply template settings
        if (isset($validated['template_id']) && $validated['template_id'] != $oldTemplateId && $validated['template_id'] !== null) {
            $templateService = app(\App\Modules\Website\Services\TemplateService::class);
            $templateService->applyTemplateToSite($validated['template_id'], $site->id);
        }

        return response()->json([
            'message' => 'Website site updated successfully.',
            'data' => $site->fresh()->load('template', 'pages'),
        ]);
    }

    /**
     * Delete a site.
     */
    public function destroy(WebsiteSite $site): JsonResponse
    {
        $site->delete();

        return response()->json([
            'message' => 'Website site deleted successfully.',
        ]);
    }

    /**
     * Create default pages based on template config.
     */
    protected function createDefaultPagesFromTemplate(WebsiteSite $site): void
    {
        $template = $site->template_id ? WebsiteTemplate::find($site->template_id) : null;
        $pages = $template?->config['pages'] ?? null;

        if (!is_array($pages) || empty($pages)) {
            WebsitePage::create([
                'site_id' => $site->id,
                'title' => 'Home',
                'slug' => 'home',
                'page_type' => 'home',
                'status' => 'published',
                'content' => [
                    'blocks' => [
                        [
                            'type' => 'hero',
                            'content' => [
                                'title' => $site->name,
                                'subtitle' => 'Welcome to your new website',
                                'ctaText' => 'Get Started',
                            ],
                            'settings' => [], // Empty settings for tenant-specific customization
                        ],
                    ],
                ],
                'published_content' => [
                    'blocks' => [
                        [
                            'type' => 'hero',
                            'content' => [
                                'title' => $site->name,
                                'subtitle' => 'Welcome to your new website',
                                'ctaText' => 'Get Started',
                            ],
                            'settings' => [], // Empty settings for tenant-specific customization
                        ],
                    ],
                ],
                'sort_order' => 1,
            ]);
            return;
        }

        $order = 1;
        foreach ($pages as $page) {
            $content = $page['content'] ?? ['blocks' => []];
            $blocks = $content['blocks'] ?? [];
            // Remove settings from blocks when copying from template (each tenant should have their own settings)
            // Ensure each block has empty settings object (tenant-specific)
            $normalizedBlocks = array_map(function ($block) {
                return [
                    'type' => $block['type'] ?? 'section',
                    'content' => $block['content'] ?? [],
                    'settings' => [], // Always start with empty settings for tenant-specific customization
                ];
            }, $blocks);
            $normalizedContent = ['blocks' => $normalizedBlocks];
            
            WebsitePage::create([
                'site_id' => $site->id,
                'title' => $page['title'] ?? 'Page',
                'slug' => $page['slug'] ?? Str::slug($page['title'] ?? 'page'),
                'page_type' => $page['page_type'] ?? 'custom',
                'status' => $page['status'] ?? 'published',
                'content' => $normalizedContent,
                'published_content' => $normalizedContent,
                'sort_order' => $order++,
                'meta' => $page['meta'] ?? null,
            ]);
        }
    }

    /**
     * Apply template settings if site settings are missing.
     */
    protected function applyTemplateSettings(WebsiteSite $site): void
    {
        if (! $site->template_id) {
            return;
        }

        $template = WebsiteTemplate::find($site->template_id);
        if (! $template || empty($template->config['theme'])) {
            return;
        }

        $currentSettings = $site->settings ?? [];
        if (! array_key_exists('theme', $currentSettings)) {
            $currentSettings['theme'] = $template->config['theme'];
            $site->update(['settings' => $currentSettings]);
        }
    }
}
