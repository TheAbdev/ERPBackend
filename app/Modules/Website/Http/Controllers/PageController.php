<?php

namespace App\Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Website\Models\WebsitePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * List pages for a site.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WebsitePage::query()->orderBy('sort_order');

        if ($request->has('site_id')) {
            $query->where('site_id', $request->input('site_id'));
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * Create page.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => ['required', 'integer', 'exists:website_sites,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'page_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            'content' => ['nullable', 'array'],
            'published_content' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'array'],
        ]);

        $page = WebsitePage::create($validated);

        return response()->json([
            'message' => 'Website page created successfully.',
            'data' => $page,
        ], 201);
    }

    /**
     * Show page.
     */
    public function show(WebsitePage $page): JsonResponse
    {
        return response()->json([
            'data' => $page,
        ]);
    }

    /**
     * Update page.
     */
    public function update(Request $request, WebsitePage $page): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'page_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:draft,published'],
            'content' => ['nullable', 'array'],
            'published_content' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'array'],
            'publish' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['publish'])) {
            // If published_content is explicitly provided, use it; otherwise copy from content
            if (!isset($validated['published_content'])) {
                $validated['published_content'] = $validated['content'] ?? $page->content;
            }
            $validated['status'] = 'published';
        }

        $page->update($validated);

        return response()->json([
            'message' => 'Website page updated successfully.',
            'data' => $page,
        ]);
    }

    /**
     * Delete page.
     */
    public function destroy(WebsitePage $page): JsonResponse
    {
        $page->delete();

        return response()->json([
            'message' => 'Website page deleted successfully.',
        ]);
    }
}
