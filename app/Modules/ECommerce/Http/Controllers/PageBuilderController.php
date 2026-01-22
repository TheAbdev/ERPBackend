<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Page;
use App\Modules\ECommerce\Models\ContentBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageBuilderController extends Controller
{
    /**
     * Get available content block types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBlockTypes(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'type' => 'text',
                    'name' => 'Text Block',
                    'icon' => 'text',
                    'description' => 'Add text content',
                ],
                [
                    'type' => 'image',
                    'name' => 'Image Block',
                    'icon' => 'image',
                    'description' => 'Add an image',
                ],
                [
                    'type' => 'products_grid',
                    'name' => 'Products Grid',
                    'icon' => 'grid',
                    'description' => 'Display products in a grid',
                ],
                [
                    'type' => 'hero',
                    'name' => 'Hero Section',
                    'icon' => 'hero',
                    'description' => 'Large banner section',
                ],
                [
                    'type' => 'video',
                    'name' => 'Video Block',
                    'icon' => 'video',
                    'description' => 'Embed a video',
                ],
                [
                    'type' => 'html',
                    'name' => 'HTML Block',
                    'icon' => 'code',
                    'description' => 'Custom HTML content',
                ],
                [
                    'type' => 'form',
                    'name' => 'Contact Form',
                    'icon' => 'form',
                    'description' => 'Contact form',
                ],
            ],
        ]);
    }

    /**
     * Save page content from page builder.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ECommerce\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePageContent(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $validated = $request->validate([
            'content' => ['required', 'array'],
        ]);

        $page->content = $validated['content'];
        $page->save();

        return response()->json([
            'message' => 'Page content saved successfully.',
            'data' => $page,
        ]);
    }

    /**
     * Get reusable content blocks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReusableBlocks(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ContentBlock::class);

        $query = ContentBlock::where('tenant_id', $request->user()->tenant_id)
            ->where('is_reusable', true);

        if ($request->has('store_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('store_id', $request->store_id)
                    ->orWhereNull('store_id');
            });
        }

        $blocks = $query->latest()->get();

        return response()->json([
            'data' => $blocks,
        ]);
    }

    /**
     * Create reusable content block.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createReusableBlock(Request $request): JsonResponse
    {
        $this->authorize('create', ContentBlock::class);

        $validated = $request->validate([
            'store_id' => ['nullable', 'exists:ecommerce_stores,id'],
            'type' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'settings' => ['sometimes', 'array'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_reusable'] = true;

        $block = ContentBlock::create($validated);

        return response()->json([
            'message' => 'Reusable block created successfully.',
            'data' => $block,
        ], 201);
    }
}








