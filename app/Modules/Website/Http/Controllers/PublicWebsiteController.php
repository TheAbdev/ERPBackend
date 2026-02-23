<?php

namespace App\Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Website\Models\WebsitePage;
use App\Modules\Website\Models\WebsiteSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicWebsiteController extends Controller
{
    /**
     * Get site by slug (public).
     */
    public function getSite(string $slug): JsonResponse
    {
        $site = WebsiteSite::withoutGlobalScopes()
            ->with('template')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $pages = WebsitePage::withoutGlobalScopes()
            ->where('site_id', $site->id)
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get();

        // Theme: prefer template.config.theme (source of truth after save) then site.settings.theme
        $siteData = $site->toArray();
        $theme = $site->template?->config['theme'] ?? ($site->settings ?? [])['theme'] ?? null;
        if ($theme) {
            $siteData['settings'] = $siteData['settings'] ?? [];
            $siteData['settings']['theme'] = $theme;
            $siteData['theme'] = $theme; // top-level so renderers always find it
        }
        if (isset($site->template) && isset($site->template->config)) {
            $siteData['template'] = $siteData['template'] ?? [];
            $siteData['template']['config'] = $site->template->config;
        }

        return response()->json([
            'data' => $siteData,
            'pages' => $pages,
        ]);
    }

    /**
     * Get page by slug (public).
     */
    public function getPage(string $slug, string $pageSlug): JsonResponse
    {
        $site = WebsiteSite::withoutGlobalScopes()
            ->with('template')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $page = WebsitePage::withoutGlobalScopes()
            ->where('site_id', $site->id)
            ->where('slug', $pageSlug)
            ->where('status', 'published')
            ->firstOrFail();

        // Theme: prefer template.config.theme then site.settings.theme; expose at top level
        $siteData = $site->toArray();
        $theme = $site->template?->config['theme'] ?? ($site->settings ?? [])['theme'] ?? null;
        if ($theme) {
            $siteData['settings'] = $siteData['settings'] ?? [];
            $siteData['settings']['theme'] = $theme;
            $siteData['theme'] = $theme;
        }
        if (isset($site->template) && isset($site->template->config)) {
            $siteData['template'] = $siteData['template'] ?? [];
            $siteData['template']['config'] = $site->template->config;
        }

        return response()->json([
            'data' => $page,
            'site' => $siteData,
        ]);
    }
}
