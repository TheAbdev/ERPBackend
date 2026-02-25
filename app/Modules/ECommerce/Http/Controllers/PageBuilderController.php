<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\ContentBlock;
use App\Modules\ECommerce\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PageBuilderController extends Controller
{
    public function getBlockTypes(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['type' => 'header', 'name' => 'Header', 'icon' => 'header', 'description' => 'Store header'],
                ['type' => 'footer', 'name' => 'Footer', 'icon' => 'footer', 'description' => 'Store footer'],
                ['type' => 'hero', 'name' => 'Hero', 'icon' => 'hero', 'description' => 'Hero section'],
                ['type' => 'banner', 'name' => 'Banner', 'icon' => 'banner', 'description' => 'Promotional banner'],
                ['type' => 'feature_grid', 'name' => 'Feature Grid', 'icon' => 'grid', 'description' => 'Feature list'],
                ['type' => 'testimonial', 'name' => 'Testimonial', 'icon' => 'quote', 'description' => 'Testimonials'],
                ['type' => 'promo_strip', 'name' => 'Promo Strip', 'icon' => 'promo', 'description' => 'Promo strip'],
                ['type' => 'products_grid', 'name' => 'Products Grid', 'icon' => 'products', 'description' => 'Products grid'],
                ['type' => 'featured-products', 'name' => 'Featured Products', 'icon' => 'products', 'description' => 'Featured products grid'],
                ['type' => 'categories', 'name' => 'Categories', 'icon' => 'categories', 'description' => 'Category grid'],
                ['type' => 'page-header', 'name' => 'Page Header', 'icon' => 'header', 'description' => 'Page header section'],
                ['type' => 'product-filters', 'name' => 'Product Filters', 'icon' => 'filters', 'description' => 'Filters for product listing'],
                ['type' => 'product-grid', 'name' => 'Product Grid', 'icon' => 'products', 'description' => 'Product listing grid'],
                ['type' => 'product-detail', 'name' => 'Product Detail', 'icon' => 'product', 'description' => 'Product detail section'],
                ['type' => 'product-tabs', 'name' => 'Product Tabs', 'icon' => 'tabs', 'description' => 'Product tabs section'],
                ['type' => 'related-products', 'name' => 'Related Products', 'icon' => 'products', 'description' => 'Related products'],
                ['type' => 'cart-items', 'name' => 'Cart Items', 'icon' => 'cart', 'description' => 'Cart items list'],
                ['type' => 'cart-summary', 'name' => 'Cart Summary', 'icon' => 'summary', 'description' => 'Cart summary'],
                ['type' => 'checkout-form', 'name' => 'Checkout Form', 'icon' => 'checkout', 'description' => 'Checkout form'],
                ['type' => 'order-history', 'name' => 'Order History', 'icon' => 'orders', 'description' => 'Order history list'],
                ['type' => 'text', 'name' => 'Text', 'icon' => 'text', 'description' => 'Text block'],
                ['type' => 'image', 'name' => 'Image', 'icon' => 'image', 'description' => 'Image block'],
                ['type' => 'button', 'name' => 'Button', 'icon' => 'button', 'description' => 'Button block'],
                ['type' => 'spacer', 'name' => 'Spacer', 'icon' => 'spacer', 'description' => 'Vertical spacing'],
                ['type' => 'divider', 'name' => 'Divider', 'icon' => 'divider', 'description' => 'Horizontal divider line'],
                ['type' => 'newsletter', 'name' => 'Newsletter', 'icon' => 'mail', 'description' => 'Newsletter signup'],
                ['type' => 'cta', 'name' => 'Call to Action', 'icon' => 'cta', 'description' => 'Call to action section'],
                ['type' => 'stats', 'name' => 'Stats / Counters', 'icon' => 'stats', 'description' => 'Numbers and stats'],
                ['type' => 'faq', 'name' => 'FAQ', 'icon' => 'faq', 'description' => 'Frequently asked questions'],
                ['type' => 'contact_form', 'name' => 'Contact Form', 'icon' => 'contact', 'description' => 'Contact form'],
                ['type' => 'video', 'name' => 'Video', 'icon' => 'video', 'description' => 'Video embed'],
                ['type' => 'social_links', 'name' => 'Social Links', 'icon' => 'share', 'description' => 'Social media links'],
                ['type' => 'logo_cloud', 'name' => 'Logo Cloud', 'icon' => 'logos', 'description' => 'Partner or brand logos'],
            ],
        ]);
    }

    public function getReusableBlocks(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ContentBlock::class);

        $query = ContentBlock::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('is_reusable', true);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        return response()->json([
            'data' => $query->latest()->get(),
        ]);
    }

    public function createReusableBlock(Request $request): JsonResponse
    {
        $this->authorize('create', ContentBlock::class);

        $validated = $request->validate([
            'store_id' => ['nullable', 'exists:ecommerce_stores,id'],
            'type' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array'],
            'settings' => ['nullable', 'array'],
        ]);

        $block = ContentBlock::create([
            'tenant_id' => $request->user()->tenant_id,
            'store_id' => $validated['store_id'] ?? null,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'content' => $validated['content'],
            'settings' => $validated['settings'] ?? [],
            'is_reusable' => true,
        ]);

        return response()->json([
            'message' => 'Reusable block created successfully.',
            'data' => $block,
        ], 201);
    }

    public function savePageContent(Request $request, Page $ecommerce_page): JsonResponse
    {
        $this->authorize('update', $ecommerce_page);

        $validated = $request->validate([
            'blocks' => ['required', 'array'],
        ]);

        if (Schema::hasColumn('ecommerce_pages', 'draft_content')) {
            $ecommerce_page->draft_content = ['blocks' => $validated['blocks']];
        } else {
            $ecommerce_page->content = ['blocks' => $validated['blocks']];
        }

        $ecommerce_page->is_published = false;
        $ecommerce_page->save();

        return response()->json([
            'message' => 'Page saved successfully.',
            'data' => $ecommerce_page,
        ]);
    }

    public function publishPageContent(Request $request, Page $ecommerce_page): JsonResponse
    {
        $this->authorize('update', $ecommerce_page);

        $validated = $request->validate([
            'blocks' => ['required', 'array'],
        ]);

        if (Schema::hasColumn('ecommerce_pages', 'published_content')) {
            $ecommerce_page->published_content = ['blocks' => $validated['blocks']];
        } else {
            $ecommerce_page->content = ['blocks' => $validated['blocks']];
        }

        $ecommerce_page->is_published = true;
        $ecommerce_page->save();

        return response()->json([
            'message' => 'Page published successfully.',
            'data' => $ecommerce_page,
        ]);
    }
}
