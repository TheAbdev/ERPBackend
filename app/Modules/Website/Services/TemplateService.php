<?php

namespace App\Modules\Website\Services;

use App\Core\Services\TenantContext;
use App\Modules\Website\Models\WebsitePage;
use App\Modules\Website\Models\WebsiteSite;
use App\Modules\Website\Models\WebsiteTemplate;
use Illuminate\Support\Str;

class TemplateService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Copy a global template to tenant-specific template
     */
    public function copyTemplate(int $templateId, ?int $tenantId = null): WebsiteTemplate
    {
        $sourceTemplate = WebsiteTemplate::findOrFail($templateId);
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();

        if (!$tenantId) {
            throw new \Exception('Tenant context is required to copy template.');
        }

        // Check if template is already copied for this tenant
        $existingCopy = WebsiteTemplate::where('tenant_id', $tenantId)
            ->where('slug', $sourceTemplate->slug)
            ->first();

        if ($existingCopy) {
            throw new \Exception('Template already copied for this tenant.');
        }

        // Create new template copy
        $newTemplate = WebsiteTemplate::create([
            'tenant_id' => $tenantId,
            'name' => $sourceTemplate->name . ' (Copy)',
            'slug' => $sourceTemplate->slug . '-' . Str::random(6),
            'description' => $sourceTemplate->description,
            'config' => $sourceTemplate->config,
            'is_active' => true,
        ]);

        return $newTemplate;
    }

    /**
     * Create a new template from scratch
     */
    public function createTemplate(array $data, ?int $tenantId = null): WebsiteTemplate
    {
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();

        $template = WebsiteTemplate::create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'config' => $data['config'] ?? [
                'theme' => [
                    'colors' => [
                        'primary' => '#0F172A',
                        'secondary' => '#F97316',
                        'background' => '#F8FAFC',
                        'text' => '#0F172A',
                    ],
                    'font' => [
                        'heading' => 'Inter',
                        'body' => 'Inter',
                    ],
                ],
                'pages' => [],
            ],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $template;
    }

    /**
     * Apply template to a site
     */
    public function applyTemplateToSite(int $templateId, int $siteId): WebsiteSite
    {
        $template = WebsiteTemplate::findOrFail($templateId);
        $site = WebsiteSite::findOrFail($siteId);

        // Update site template
        $site->update([
            'template_id' => $templateId,
        ]);

        // Apply template settings
        $this->applyTemplateSettings($site, $template);

        // Sync template pages
        $this->syncTemplatePages($template, $site);

        return $site->fresh(['template', 'pages']);
    }

    /**
     * Apply template settings to site
     */
    protected function applyTemplateSettings(WebsiteSite $site, WebsiteTemplate $template): void
    {
        if (empty($template->config['theme'])) {
            return;
        }

        $currentSettings = $site->settings ?? [];
        // Always update theme when applying a new template
        $currentSettings['theme'] = $template->config['theme'];
        $site->update(['settings' => $currentSettings]);
    }

    /**
     * Sync pages from template to site
     */
    protected function syncTemplatePages(WebsiteTemplate $template, WebsiteSite $site): void
    {
        $templatePages = $template->config['pages'] ?? [];

        if (empty($templatePages)) {
            // Create default home page if no template pages
            $this->createDefaultPage($site);
            return;
        }

        // Delete existing pages (optional - you might want to keep them)
        // $site->pages()->delete();

        // Create pages from template config
        $order = 1;
        foreach ($templatePages as $pageConfig) {
            $existingPage = $site->pages()
                ->where('slug', $pageConfig['slug'] ?? Str::slug($pageConfig['title'] ?? 'page'))
                ->first();

            if (!$existingPage) {
                // Remove settings from blocks when copying from template (each tenant should have their own settings)
                $content = $pageConfig['content'] ?? ['blocks' => []];
                $blocks = $content['blocks'] ?? [];
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
                    'title' => $pageConfig['title'] ?? 'Page',
                    'slug' => $pageConfig['slug'] ?? Str::slug($pageConfig['title'] ?? 'page'),
                    'page_type' => $pageConfig['page_type'] ?? 'custom',
                    'status' => $pageConfig['status'] ?? 'published',
                    'content' => $normalizedContent,
                    'published_content' => $normalizedContent,
                    'sort_order' => $order++,
                    'meta' => $pageConfig['meta'] ?? null,
                ]);
            }
        }
    }

    /**
     * Create default home page
     */
    protected function createDefaultPage(WebsiteSite $site): void
    {
        $existingHome = $site->pages()->where('page_type', 'home')->first();

        if (!$existingHome) {
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
                        ],
                    ],
                ],
                'sort_order' => 1,
            ]);
        }
    }

    /**
     * Update template
     */
    public function updateTemplate(int $templateId, array $data): WebsiteTemplate
    {
        $template = WebsiteTemplate::findOrFail($templateId);

        // Check if theme config is being updated
        $themeUpdated = false;
        $newThemeConfig = null;
        if (isset($data['config']['theme'])) {
            $themeUpdated = true;
            $newThemeConfig = $data['config']['theme'];
        }

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['slug'])) {
            $updateData['slug'] = $data['slug'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['config'])) {
            $updateData['config'] = $data['config'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        $template->update($updateData);

        // If theme was updated, update all sites that use this template
        if ($themeUpdated && $newThemeConfig) {
            $this->updateSitesTheme($templateId, $newThemeConfig);
        }

        return $template->fresh();
    }

    /**
     * Update theme settings for all sites using this template
     */
    protected function updateSitesTheme(int $templateId, array $themeConfig): void
    {
        // Find all sites that use this template
        $sites = WebsiteSite::where('template_id', $templateId)->get();

        foreach ($sites as $site) {
            $currentSettings = $site->settings ?? [];
            // Update theme in site settings
            $currentSettings['theme'] = $themeConfig;
            $site->update(['settings' => $currentSettings]);
        }
    }

    /**
     * Delete template (only if tenant-specific)
     */
    public function deleteTemplate(int $templateId): bool
    {
        $template = WebsiteTemplate::findOrFail($templateId);
        $tenantId = $this->tenantContext->getTenantId();

        // Only allow deletion of tenant-specific templates
        if ($template->tenant_id !== $tenantId) {
            throw new \Exception('Cannot delete global template.');
        }

        return $template->delete();
    }
}

