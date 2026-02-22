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

        return response()->json([
            'data' => $site,
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

        return response()->json([
            'data' => $page,
            'site' => $site,
        ]);
    }
}
