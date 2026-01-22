<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ThemeController extends Controller
{
    /**
     * Display a listing of themes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Theme::class);

        $query = Theme::where('tenant_id', $request->user()->tenant_id);

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

    /**
     * Store a newly created theme.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        $tenantId = $request->user()->tenant_id;
        $validated['tenant_id'] = $tenantId;
        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        
        // Ensure slug is unique per tenant
        $baseSlug = $slug;
        $counter = 1;
        while (Theme::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;
        
        $validated['is_active'] = $request->input('is_active', true);
        $validated['is_default'] = $request->input('is_default', false);

        $theme = Theme::create($validated);

        return response()->json([
            'message' => 'Theme created successfully.',
            'data' => $theme,
        ], 201);
    }

    /**
     * Display the specified theme.
     *
     * @param  \App\Modules\ECommerce\Models\Theme  $theme
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Theme $theme): JsonResponse
    {
        $this->authorize('view', $theme);

        return response()->json([
            'data' => $theme,
        ]);
    }

    /**
     * Update the specified theme.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ECommerce\Models\Theme  $theme
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Theme $theme): JsonResponse
    {
        $this->authorize('update', $theme);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'assets' => ['sometimes', 'array'],
            'preview_image' => ['nullable', 'string'],
        ]);

        // Ensure slug is unique per tenant
        if (isset($validated['slug']) && $validated['slug'] !== $theme->slug) {
            $tenantId = $theme->tenant_id;
            $slug = $validated['slug'];
            $baseSlug = $slug;
            $counter = 1;
            while (Theme::where('tenant_id', $tenantId)->where('slug', $slug)->where('id', '!=', $theme->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $theme->update($validated);

        return response()->json([
            'message' => 'Theme updated successfully.',
            'data' => $theme,
        ]);
    }

    /**
     * Remove the specified theme.
     *
     * @param  \App\Modules\ECommerce\Models\Theme  $theme
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Theme $theme): JsonResponse
    {
        $this->authorize('delete', $theme);

        $theme->delete();

        return response()->json([
            'message' => 'Theme deleted successfully.',
        ]);
    }

    /**
     * Get available theme templates.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function templates(): JsonResponse
    {
        $this->authorize('create', Theme::class);

        $templates = [
            [
                'name' => 'Modern Minimal',
                'slug' => 'modern-minimal',
                'description' => 'A modern, clean design with minimal styling',
                'preview_image' => null,
                'config' => [
                    'colors' => [
                        'primary' => '#3B82F6',
                        'secondary' => '#64748B',
                        'background' => '#FFFFFF',
                        'text' => '#1E293B',
                    ],
                    'typography' => [
                        'fontFamily' => 'Inter',
                        'headingSize' => '2rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'minimal',
                            'sticky' => true,
                        ],
                        'footer' => [
                            'style' => 'simple',
                        ],
                    ],
                    'translations' => [
                        'en' => [
                            'home' => 'Home',
                            'products' => 'Products',
                            'cart' => 'Cart',
                            'shop_now' => 'Shop Now',
                            'add_to_cart' => 'Add to Cart',
                            'in_stock' => 'In Stock',
                            'out_of_stock' => 'Out of Stock',
                            'loading' => 'Loading...',
                            'loading_products' => 'Loading products...',
                            'loading_product' => 'Loading product...',
                            'loading_cart' => 'Loading cart...',
                            'no_products' => 'No products available yet.',
                            'no_products_found' => 'No products found.',
                            'product_not_found' => 'Product Not Found',
                            'store_not_found' => 'Store Not Found',
                            'store_not_found_desc' => 'The store you\'re looking for doesn\'t exist.',
                            'quantity' => 'Quantity',
                            'price' => 'Price',
                            'total' => 'Total',
                            'subtotal' => 'Subtotal',
                            'checkout' => 'Checkout',
                            'continue_shopping' => 'Continue Shopping',
                            'your_cart_is_empty' => 'Your cart is empty',
                            'search_products' => 'Search products...',
                            'category' => 'Category',
                            'description' => 'Description',
                            'remove' => 'Remove',
                            'update' => 'Update',
                            'all_rights_reserved' => 'All rights reserved.',
                            'welcome' => 'Welcome to',
                            'featured_products' => 'Featured Products',
                            'back_to_products' => 'Back to Products',
                            'stock_status' => 'Stock Status',
                            'available' => 'available',
                            'adding' => 'Adding...',
                            'shopping_cart' => 'Shopping Cart',
                            'order_summary' => 'Order Summary',
                            'tax' => 'Tax',
                            'shipping' => 'Shipping',
                            'proceed_to_checkout' => 'Proceed to Checkout',
                            'each' => 'each',
                            'item_removed' => 'Item removed from cart',
                            'cart_updated' => 'Cart updated',
                        ],
                        'ar' => [
                            'home' => 'الرئيسية',
                            'products' => 'المنتجات',
                            'cart' => 'السلة',
                            'shop_now' => 'تسوق الآن',
                            'add_to_cart' => 'أضف إلى السلة',
                            'in_stock' => 'متوفر',
                            'out_of_stock' => 'غير متوفر',
                            'loading' => 'جاري التحميل...',
                            'loading_products' => 'جاري تحميل المنتجات...',
                            'loading_product' => 'جاري تحميل المنتج...',
                            'loading_cart' => 'جاري تحميل السلة...',
                            'no_products' => 'لا توجد منتجات متاحة حالياً.',
                            'no_products_found' => 'لم يتم العثور على منتجات.',
                            'product_not_found' => 'المنتج غير موجود',
                            'store_not_found' => 'المتجر غير موجود',
                            'store_not_found_desc' => 'المتجر الذي تبحث عنه غير موجود.',
                            'quantity' => 'الكمية',
                            'price' => 'السعر',
                            'total' => 'الإجمالي',
                            'subtotal' => 'المجموع الفرعي',
                            'checkout' => 'الدفع',
                            'continue_shopping' => 'متابعة التسوق',
                            'your_cart_is_empty' => 'سلة التسوق فارغة',
                            'search_products' => 'ابحث عن المنتجات...',
                            'category' => 'الفئة',
                            'description' => 'الوصف',
                            'remove' => 'إزالة',
                            'update' => 'تحديث',
                            'all_rights_reserved' => 'جميع الحقوق محفوظة.',
                            'welcome' => 'مرحباً بك في',
                            'featured_products' => 'المنتجات المميزة',
                            'back_to_products' => 'العودة إلى المنتجات',
                            'stock_status' => 'حالة المخزون',
                            'available' => 'متوفر',
                            'adding' => 'جاري الإضافة...',
                            'shopping_cart' => 'سلة التسوق',
                            'order_summary' => 'ملخص الطلب',
                            'tax' => 'الضريبة',
                            'shipping' => 'الشحن',
                            'proceed_to_checkout' => 'المتابعة إلى الدفع',
                            'each' => 'لكل',
                            'item_removed' => 'تم إزالة العنصر من السلة',
                            'cart_updated' => 'تم تحديث السلة',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Classic Shop',
                'slug' => 'classic-shop',
                'description' => 'A traditional e-commerce design',
                'preview_image' => null,
                'config' => [
                    'colors' => [
                        'primary' => '#8B4513',
                        'secondary' => '#D2B48C',
                        'background' => '#FFF8DC',
                        'text' => '#654321',
                    ],
                    'typography' => [
                        'fontFamily' => 'Georgia',
                        'headingSize' => '2.5rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'classic',
                            'sticky' => false,
                        ],
                        'footer' => [
                            'style' => 'detailed',
                        ],
                    ],
                    'translations' => [
                        'en' => [
                            'home' => 'Home',
                            'products' => 'Products',
                            'cart' => 'Cart',
                            'shop_now' => 'Shop Now',
                            'add_to_cart' => 'Add to Cart',
                            'in_stock' => 'In Stock',
                            'out_of_stock' => 'Out of Stock',
                            'loading' => 'Loading...',
                            'loading_products' => 'Loading products...',
                            'loading_product' => 'Loading product...',
                            'loading_cart' => 'Loading cart...',
                            'no_products' => 'No products available yet.',
                            'no_products_found' => 'No products found.',
                            'product_not_found' => 'Product Not Found',
                            'store_not_found' => 'Store Not Found',
                            'store_not_found_desc' => 'The store you\'re looking for doesn\'t exist.',
                            'quantity' => 'Quantity',
                            'price' => 'Price',
                            'total' => 'Total',
                            'subtotal' => 'Subtotal',
                            'checkout' => 'Checkout',
                            'continue_shopping' => 'Continue Shopping',
                            'your_cart_is_empty' => 'Your cart is empty',
                            'search_products' => 'Search products...',
                            'category' => 'Category',
                            'description' => 'Description',
                            'remove' => 'Remove',
                            'update' => 'Update',
                            'all_rights_reserved' => 'All rights reserved.',
                            'welcome' => 'Welcome to',
                            'featured_products' => 'Featured Products',
                            'back_to_products' => 'Back to Products',
                            'stock_status' => 'Stock Status',
                            'available' => 'available',
                            'adding' => 'Adding...',
                            'shopping_cart' => 'Shopping Cart',
                            'order_summary' => 'Order Summary',
                            'tax' => 'Tax',
                            'shipping' => 'Shipping',
                            'proceed_to_checkout' => 'Proceed to Checkout',
                            'each' => 'each',
                            'item_removed' => 'Item removed from cart',
                            'cart_updated' => 'Cart updated',
                        ],
                        'ar' => [
                            'home' => 'الرئيسية',
                            'products' => 'المنتجات',
                            'cart' => 'السلة',
                            'shop_now' => 'تسوق الآن',
                            'add_to_cart' => 'أضف إلى السلة',
                            'in_stock' => 'متوفر',
                            'out_of_stock' => 'غير متوفر',
                            'loading' => 'جاري التحميل...',
                            'loading_products' => 'جاري تحميل المنتجات...',
                            'loading_product' => 'جاري تحميل المنتج...',
                            'loading_cart' => 'جاري تحميل السلة...',
                            'no_products' => 'لا توجد منتجات متاحة حالياً.',
                            'no_products_found' => 'لم يتم العثور على منتجات.',
                            'product_not_found' => 'المنتج غير موجود',
                            'store_not_found' => 'المتجر غير موجود',
                            'store_not_found_desc' => 'المتجر الذي تبحث عنه غير موجود.',
                            'quantity' => 'الكمية',
                            'price' => 'السعر',
                            'total' => 'الإجمالي',
                            'subtotal' => 'المجموع الفرعي',
                            'checkout' => 'الدفع',
                            'continue_shopping' => 'متابعة التسوق',
                            'your_cart_is_empty' => 'سلة التسوق فارغة',
                            'search_products' => 'ابحث عن المنتجات...',
                            'category' => 'الفئة',
                            'description' => 'الوصف',
                            'remove' => 'إزالة',
                            'update' => 'تحديث',
                            'all_rights_reserved' => 'جميع الحقوق محفوظة.',
                            'welcome' => 'مرحباً بك في',
                            'featured_products' => 'المنتجات المميزة',
                            'back_to_products' => 'العودة إلى المنتجات',
                            'stock_status' => 'حالة المخزون',
                            'available' => 'متوفر',
                            'adding' => 'جاري الإضافة...',
                            'shopping_cart' => 'سلة التسوق',
                            'order_summary' => 'ملخص الطلب',
                            'tax' => 'الضريبة',
                            'shipping' => 'الشحن',
                            'proceed_to_checkout' => 'المتابعة إلى الدفع',
                            'each' => 'لكل',
                            'item_removed' => 'تم إزالة العنصر من السلة',
                            'cart_updated' => 'تم تحديث السلة',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Bold Commerce',
                'slug' => 'bold-commerce',
                'description' => 'A bold, eye-catching design',
                'preview_image' => null,
                'config' => [
                    'colors' => [
                        'primary' => '#000000',
                        'secondary' => '#DC2626',
                        'background' => '#FFFFFF',
                        'text' => '#000000',
                    ],
                    'typography' => [
                        'fontFamily' => 'Arial Black',
                        'headingSize' => '3rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'bold',
                            'sticky' => true,
                        ],
                        'footer' => [
                            'style' => 'minimal',
                        ],
                    ],
                    'translations' => [
                        'en' => [
                            'home' => 'Home',
                            'products' => 'Products',
                            'cart' => 'Cart',
                            'shop_now' => 'Shop Now',
                            'add_to_cart' => 'Add to Cart',
                            'in_stock' => 'In Stock',
                            'out_of_stock' => 'Out of Stock',
                            'loading' => 'Loading...',
                            'loading_products' => 'Loading products...',
                            'loading_product' => 'Loading product...',
                            'loading_cart' => 'Loading cart...',
                            'no_products' => 'No products available yet.',
                            'no_products_found' => 'No products found.',
                            'product_not_found' => 'Product Not Found',
                            'store_not_found' => 'Store Not Found',
                            'store_not_found_desc' => 'The store you\'re looking for doesn\'t exist.',
                            'quantity' => 'Quantity',
                            'price' => 'Price',
                            'total' => 'Total',
                            'subtotal' => 'Subtotal',
                            'checkout' => 'Checkout',
                            'continue_shopping' => 'Continue Shopping',
                            'your_cart_is_empty' => 'Your cart is empty',
                            'search_products' => 'Search products...',
                            'category' => 'Category',
                            'description' => 'Description',
                            'remove' => 'Remove',
                            'update' => 'Update',
                            'all_rights_reserved' => 'All rights reserved.',
                            'welcome' => 'Welcome to',
                            'featured_products' => 'Featured Products',
                            'back_to_products' => 'Back to Products',
                            'stock_status' => 'Stock Status',
                            'available' => 'available',
                            'adding' => 'Adding...',
                            'shopping_cart' => 'Shopping Cart',
                            'order_summary' => 'Order Summary',
                            'tax' => 'Tax',
                            'shipping' => 'Shipping',
                            'proceed_to_checkout' => 'Proceed to Checkout',
                            'each' => 'each',
                            'item_removed' => 'Item removed from cart',
                            'cart_updated' => 'Cart updated',
                        ],
                        'ar' => [
                            'home' => 'الرئيسية',
                            'products' => 'المنتجات',
                            'cart' => 'السلة',
                            'shop_now' => 'تسوق الآن',
                            'add_to_cart' => 'أضف إلى السلة',
                            'in_stock' => 'متوفر',
                            'out_of_stock' => 'غير متوفر',
                            'loading' => 'جاري التحميل...',
                            'loading_products' => 'جاري تحميل المنتجات...',
                            'loading_product' => 'جاري تحميل المنتج...',
                            'loading_cart' => 'جاري تحميل السلة...',
                            'no_products' => 'لا توجد منتجات متاحة حالياً.',
                            'no_products_found' => 'لم يتم العثور على منتجات.',
                            'product_not_found' => 'المنتج غير موجود',
                            'store_not_found' => 'المتجر غير موجود',
                            'store_not_found_desc' => 'المتجر الذي تبحث عنه غير موجود.',
                            'quantity' => 'الكمية',
                            'price' => 'السعر',
                            'total' => 'الإجمالي',
                            'subtotal' => 'المجموع الفرعي',
                            'checkout' => 'الدفع',
                            'continue_shopping' => 'متابعة التسوق',
                            'your_cart_is_empty' => 'سلة التسوق فارغة',
                            'search_products' => 'ابحث عن المنتجات...',
                            'category' => 'الفئة',
                            'description' => 'الوصف',
                            'remove' => 'إزالة',
                            'update' => 'تحديث',
                            'all_rights_reserved' => 'جميع الحقوق محفوظة.',
                            'welcome' => 'مرحباً بك في',
                            'featured_products' => 'المنتجات المميزة',
                            'back_to_products' => 'العودة إلى المنتجات',
                            'stock_status' => 'حالة المخزون',
                            'available' => 'متوفر',
                            'adding' => 'جاري الإضافة...',
                            'shopping_cart' => 'سلة التسوق',
                            'order_summary' => 'ملخص الطلب',
                            'tax' => 'الضريبة',
                            'shipping' => 'الشحن',
                            'proceed_to_checkout' => 'المتابعة إلى الدفع',
                            'each' => 'لكل',
                            'item_removed' => 'تم إزالة العنصر من السلة',
                            'cart_updated' => 'تم تحديث السلة',
                        ],
                    ],
                ],
            ],
        ];

        return response()->json([
            'data' => $templates,
        ]);
    }

    /**
     * Create theme from template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFromTemplate(Request $request): JsonResponse
    {
        $this->authorize('create', Theme::class);

        $validated = $request->validate([
            'template_slug' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        // Get template
        $templates = [
            'modern-minimal' => [
                'name' => 'Modern Minimal',
                'slug' => 'modern-minimal',
                'description' => 'A modern, clean design with minimal styling',
                'config' => [
                    'colors' => [
                        'primary' => '#3B82F6',
                        'secondary' => '#64748B',
                        'background' => '#FFFFFF',
                        'text' => '#1E293B',
                    ],
                    'typography' => [
                        'fontFamily' => 'Inter',
                        'headingSize' => '2rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'minimal',
                            'sticky' => true,
                        ],
                        'footer' => [
                            'style' => 'simple',
                        ],
                    ],
                    'translations' => [
                        'en' => [
                            'home' => 'Home',
                            'products' => 'Products',
                            'cart' => 'Cart',
                            'shop_now' => 'Shop Now',
                            'add_to_cart' => 'Add to Cart',
                            'in_stock' => 'In Stock',
                            'out_of_stock' => 'Out of Stock',
                            'loading' => 'Loading...',
                            'loading_products' => 'Loading products...',
                            'loading_product' => 'Loading product...',
                            'loading_cart' => 'Loading cart...',
                            'no_products' => 'No products available yet.',
                            'no_products_found' => 'No products found.',
                            'product_not_found' => 'Product Not Found',
                            'store_not_found' => 'Store Not Found',
                            'store_not_found_desc' => 'The store you\'re looking for doesn\'t exist.',
                            'quantity' => 'Quantity',
                            'price' => 'Price',
                            'total' => 'Total',
                            'subtotal' => 'Subtotal',
                            'checkout' => 'Checkout',
                            'continue_shopping' => 'Continue Shopping',
                            'your_cart_is_empty' => 'Your cart is empty',
                            'search_products' => 'Search products...',
                            'category' => 'Category',
                            'description' => 'Description',
                            'remove' => 'Remove',
                            'update' => 'Update',
                            'all_rights_reserved' => 'All rights reserved.',
                            'welcome' => 'Welcome to',
                            'featured_products' => 'Featured Products',
                            'back_to_products' => 'Back to Products',
                            'stock_status' => 'Stock Status',
                            'available' => 'available',
                            'adding' => 'Adding...',
                            'shopping_cart' => 'Shopping Cart',
                            'order_summary' => 'Order Summary',
                            'tax' => 'Tax',
                            'shipping' => 'Shipping',
                            'proceed_to_checkout' => 'Proceed to Checkout',
                            'each' => 'each',
                            'item_removed' => 'Item removed from cart',
                            'cart_updated' => 'Cart updated',
                        ],
                        'ar' => [
                            'home' => 'الرئيسية',
                            'products' => 'المنتجات',
                            'cart' => 'السلة',
                            'shop_now' => 'تسوق الآن',
                            'add_to_cart' => 'أضف إلى السلة',
                            'in_stock' => 'متوفر',
                            'out_of_stock' => 'غير متوفر',
                            'loading' => 'جاري التحميل...',
                            'loading_products' => 'جاري تحميل المنتجات...',
                            'loading_product' => 'جاري تحميل المنتج...',
                            'loading_cart' => 'جاري تحميل السلة...',
                            'no_products' => 'لا توجد منتجات متاحة حالياً.',
                            'no_products_found' => 'لم يتم العثور على منتجات.',
                            'product_not_found' => 'المنتج غير موجود',
                            'store_not_found' => 'المتجر غير موجود',
                            'store_not_found_desc' => 'المتجر الذي تبحث عنه غير موجود.',
                            'quantity' => 'الكمية',
                            'price' => 'السعر',
                            'total' => 'الإجمالي',
                            'subtotal' => 'المجموع الفرعي',
                            'checkout' => 'الدفع',
                            'continue_shopping' => 'متابعة التسوق',
                            'your_cart_is_empty' => 'سلة التسوق فارغة',
                            'search_products' => 'ابحث عن المنتجات...',
                            'category' => 'الفئة',
                            'description' => 'الوصف',
                            'remove' => 'إزالة',
                            'update' => 'تحديث',
                            'all_rights_reserved' => 'جميع الحقوق محفوظة.',
                            'welcome' => 'مرحباً بك في',
                            'featured_products' => 'المنتجات المميزة',
                            'back_to_products' => 'العودة إلى المنتجات',
                            'stock_status' => 'حالة المخزون',
                            'available' => 'متوفر',
                            'adding' => 'جاري الإضافة...',
                            'shopping_cart' => 'سلة التسوق',
                            'order_summary' => 'ملخص الطلب',
                            'tax' => 'الضريبة',
                            'shipping' => 'الشحن',
                            'proceed_to_checkout' => 'المتابعة إلى الدفع',
                            'each' => 'لكل',
                            'item_removed' => 'تم إزالة العنصر من السلة',
                            'cart_updated' => 'تم تحديث السلة',
                        ],
                    ],
                ],
            ],
            'classic-shop' => [
                'name' => 'Classic Shop',
                'slug' => 'classic-shop',
                'description' => 'A traditional e-commerce design',
                'config' => [
                    'colors' => [
                        'primary' => '#8B4513',
                        'secondary' => '#D2B48C',
                        'background' => '#FFF8DC',
                        'text' => '#654321',
                    ],
                    'typography' => [
                        'fontFamily' => 'Georgia',
                        'headingSize' => '2.5rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'classic',
                            'sticky' => false,
                        ],
                        'footer' => [
                            'style' => 'detailed',
                        ],
                    ],
                    'translations' => [
                        'en' => [
                            'home' => 'Home',
                            'products' => 'Products',
                            'cart' => 'Cart',
                            'shop_now' => 'Shop Now',
                            'add_to_cart' => 'Add to Cart',
                            'in_stock' => 'In Stock',
                            'out_of_stock' => 'Out of Stock',
                            'loading' => 'Loading...',
                            'loading_products' => 'Loading products...',
                            'loading_product' => 'Loading product...',
                            'loading_cart' => 'Loading cart...',
                            'no_products' => 'No products available yet.',
                            'no_products_found' => 'No products found.',
                            'product_not_found' => 'Product Not Found',
                            'store_not_found' => 'Store Not Found',
                            'store_not_found_desc' => 'The store you\'re looking for doesn\'t exist.',
                            'quantity' => 'Quantity',
                            'price' => 'Price',
                            'total' => 'Total',
                            'subtotal' => 'Subtotal',
                            'checkout' => 'Checkout',
                            'continue_shopping' => 'Continue Shopping',
                            'your_cart_is_empty' => 'Your cart is empty',
                            'search_products' => 'Search products...',
                            'category' => 'Category',
                            'description' => 'Description',
                            'remove' => 'Remove',
                            'update' => 'Update',
                            'all_rights_reserved' => 'All rights reserved.',
                            'welcome' => 'Welcome to',
                            'featured_products' => 'Featured Products',
                            'back_to_products' => 'Back to Products',
                            'stock_status' => 'Stock Status',
                            'available' => 'available',
                            'adding' => 'Adding...',
                            'shopping_cart' => 'Shopping Cart',
                            'order_summary' => 'Order Summary',
                            'tax' => 'Tax',
                            'shipping' => 'Shipping',
                            'proceed_to_checkout' => 'Proceed to Checkout',
                            'each' => 'each',
                            'item_removed' => 'Item removed from cart',
                            'cart_updated' => 'Cart updated',
                        ],
                        'ar' => [
                            'home' => 'الرئيسية',
                            'products' => 'المنتجات',
                            'cart' => 'السلة',
                            'shop_now' => 'تسوق الآن',
                            'add_to_cart' => 'أضف إلى السلة',
                            'in_stock' => 'متوفر',
                            'out_of_stock' => 'غير متوفر',
                            'loading' => 'جاري التحميل...',
                            'loading_products' => 'جاري تحميل المنتجات...',
                            'loading_product' => 'جاري تحميل المنتج...',
                            'loading_cart' => 'جاري تحميل السلة...',
                            'no_products' => 'لا توجد منتجات متاحة حالياً.',
                            'no_products_found' => 'لم يتم العثور على منتجات.',
                            'product_not_found' => 'المنتج غير موجود',
                            'store_not_found' => 'المتجر غير موجود',
                            'store_not_found_desc' => 'المتجر الذي تبحث عنه غير موجود.',
                            'quantity' => 'الكمية',
                            'price' => 'السعر',
                            'total' => 'الإجمالي',
                            'subtotal' => 'المجموع الفرعي',
                            'checkout' => 'الدفع',
                            'continue_shopping' => 'متابعة التسوق',
                            'your_cart_is_empty' => 'سلة التسوق فارغة',
                            'search_products' => 'ابحث عن المنتجات...',
                            'category' => 'الفئة',
                            'description' => 'الوصف',
                            'remove' => 'إزالة',
                            'update' => 'تحديث',
                            'all_rights_reserved' => 'جميع الحقوق محفوظة.',
                            'welcome' => 'مرحباً بك في',
                            'featured_products' => 'المنتجات المميزة',
                            'back_to_products' => 'العودة إلى المنتجات',
                            'stock_status' => 'حالة المخزون',
                            'available' => 'متوفر',
                            'adding' => 'جاري الإضافة...',
                            'shopping_cart' => 'سلة التسوق',
                            'order_summary' => 'ملخص الطلب',
                            'tax' => 'الضريبة',
                            'shipping' => 'الشحن',
                            'proceed_to_checkout' => 'المتابعة إلى الدفع',
                            'each' => 'لكل',
                            'item_removed' => 'تم إزالة العنصر من السلة',
                            'cart_updated' => 'تم تحديث السلة',
                        ],
                    ],
                ],
            ],
            'bold-commerce' => [
                'name' => 'Bold Commerce',
                'slug' => 'bold-commerce',
                'description' => 'A bold, eye-catching design',
                'config' => [
                    'colors' => [
                        'primary' => '#000000',
                        'secondary' => '#DC2626',
                        'background' => '#FFFFFF',
                        'text' => '#000000',
                    ],
                    'typography' => [
                        'fontFamily' => 'Arial Black',
                        'headingSize' => '3rem',
                    ],
                    'layout' => [
                        'header' => [
                            'style' => 'bold',
                            'sticky' => true,
                        ],
                        'footer' => [
                            'style' => 'minimal',
                        ],
                    ],
                    'translations' => [
                        'en' => [
                            'home' => 'Home',
                            'products' => 'Products',
                            'cart' => 'Cart',
                            'shop_now' => 'Shop Now',
                            'add_to_cart' => 'Add to Cart',
                            'in_stock' => 'In Stock',
                            'out_of_stock' => 'Out of Stock',
                            'loading' => 'Loading...',
                            'loading_products' => 'Loading products...',
                            'loading_product' => 'Loading product...',
                            'loading_cart' => 'Loading cart...',
                            'no_products' => 'No products available yet.',
                            'no_products_found' => 'No products found.',
                            'product_not_found' => 'Product Not Found',
                            'store_not_found' => 'Store Not Found',
                            'store_not_found_desc' => 'The store you\'re looking for doesn\'t exist.',
                            'quantity' => 'Quantity',
                            'price' => 'Price',
                            'total' => 'Total',
                            'subtotal' => 'Subtotal',
                            'checkout' => 'Checkout',
                            'continue_shopping' => 'Continue Shopping',
                            'your_cart_is_empty' => 'Your cart is empty',
                            'search_products' => 'Search products...',
                            'category' => 'Category',
                            'description' => 'Description',
                            'remove' => 'Remove',
                            'update' => 'Update',
                            'all_rights_reserved' => 'All rights reserved.',
                            'welcome' => 'Welcome to',
                            'featured_products' => 'Featured Products',
                            'back_to_products' => 'Back to Products',
                            'stock_status' => 'Stock Status',
                            'available' => 'available',
                            'adding' => 'Adding...',
                            'shopping_cart' => 'Shopping Cart',
                            'order_summary' => 'Order Summary',
                            'tax' => 'Tax',
                            'shipping' => 'Shipping',
                            'proceed_to_checkout' => 'Proceed to Checkout',
                            'each' => 'each',
                            'item_removed' => 'Item removed from cart',
                            'cart_updated' => 'Cart updated',
                        ],
                        'ar' => [
                            'home' => 'الرئيسية',
                            'products' => 'المنتجات',
                            'cart' => 'السلة',
                            'shop_now' => 'تسوق الآن',
                            'add_to_cart' => 'أضف إلى السلة',
                            'in_stock' => 'متوفر',
                            'out_of_stock' => 'غير متوفر',
                            'loading' => 'جاري التحميل...',
                            'loading_products' => 'جاري تحميل المنتجات...',
                            'loading_product' => 'جاري تحميل المنتج...',
                            'loading_cart' => 'جاري تحميل السلة...',
                            'no_products' => 'لا توجد منتجات متاحة حالياً.',
                            'no_products_found' => 'لم يتم العثور على منتجات.',
                            'product_not_found' => 'المنتج غير موجود',
                            'store_not_found' => 'المتجر غير موجود',
                            'store_not_found_desc' => 'المتجر الذي تبحث عنه غير موجود.',
                            'quantity' => 'الكمية',
                            'price' => 'السعر',
                            'total' => 'الإجمالي',
                            'subtotal' => 'المجموع الفرعي',
                            'checkout' => 'الدفع',
                            'continue_shopping' => 'متابعة التسوق',
                            'your_cart_is_empty' => 'سلة التسوق فارغة',
                            'search_products' => 'ابحث عن المنتجات...',
                            'category' => 'الفئة',
                            'description' => 'الوصف',
                            'remove' => 'إزالة',
                            'update' => 'تحديث',
                            'all_rights_reserved' => 'جميع الحقوق محفوظة.',
                            'welcome' => 'مرحباً بك في',
                            'featured_products' => 'المنتجات المميزة',
                            'back_to_products' => 'العودة إلى المنتجات',
                            'stock_status' => 'حالة المخزون',
                            'available' => 'متوفر',
                            'adding' => 'جاري الإضافة...',
                            'shopping_cart' => 'سلة التسوق',
                            'order_summary' => 'ملخص الطلب',
                            'tax' => 'الضريبة',
                            'shipping' => 'الشحن',
                            'proceed_to_checkout' => 'المتابعة إلى الدفع',
                            'each' => 'لكل',
                            'item_removed' => 'تم إزالة العنصر من السلة',
                            'cart_updated' => 'تم تحديث السلة',
                        ],
                    ],
                ],
            ],
        ];

        if (!isset($templates[$validated['template_slug']])) {
            return response()->json([
                'message' => 'Template not found.',
            ], 404);
        }

        $template = $templates[$validated['template_slug']];
        $tenantId = $request->user()->tenant_id;

        // Generate unique slug (must be unique globally, not just per tenant)
        $baseName = $validated['name'] ?? $template['name'];
        $baseSlug = Str::slug($baseName);
        
        // Always start with a unique slug by adding tenant ID and timestamp
        // This ensures uniqueness even if multiple tenants create themes from the same template
        $timestamp = time();
        $slug = $baseSlug . '-' . $tenantId . '-' . $timestamp;
        
        // Double-check if slug exists (very unlikely but possible)
        $counter = 1;
        $maxAttempts = 100;
        while (Theme::where('slug', $slug)->exists() && $counter <= $maxAttempts) {
            $slug = $baseSlug . '-' . $tenantId . '-' . $timestamp . '-' . $counter;
            $counter++;
        }
        
        // Final fallback if all else fails
        if ($counter > $maxAttempts || Theme::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $tenantId . '-' . uniqid();
        }

        // Final verification before creating
        if (Theme::where('slug', $slug)->exists()) {
            // Last resort: use microtime for absolute uniqueness
            $slug = $baseSlug . '-' . $tenantId . '-' . microtime(true);
        }

        try {
            $theme = Theme::create([
                'tenant_id' => $tenantId,
                'name' => $baseName,
                'slug' => $slug,
                'description' => $template['description'],
                'is_active' => true,
                'is_default' => $request->input('is_default', false),
                'config' => $template['config'],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // If still fails, use microtime as absolute fallback
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $slug = $baseSlug . '-' . $tenantId . '-' . microtime(true);
                $theme = Theme::create([
                    'tenant_id' => $tenantId,
                    'name' => $baseName,
                    'slug' => $slug,
                    'description' => $template['description'],
                    'is_active' => true,
                    'is_default' => $request->input('is_default', false),
                    'config' => $template['config'],
                ]);
            } else {
                throw $e;
            }
        }

        return response()->json([
            'message' => 'Theme created from template successfully.',
            'data' => $theme,
        ], 201);
    }
}

