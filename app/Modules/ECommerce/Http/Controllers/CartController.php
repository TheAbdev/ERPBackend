<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Cart;
use App\Modules\ECommerce\Models\ProductSync;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ERP\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Get cart for a storefront session (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCart(Request $request, string $storeSlug): JsonResponse
    {
        $store = $this->resolveStore($storeSlug);
        $sessionId = $this->resolveSessionId($request);

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'tenant_id' => $store->tenant_id,
                'store_id' => $store->id,
                'session_id' => $sessionId,
                'items' => [],
                'currency' => $store->settings['currency'] ?? 'USD',
            ]);
        }

        $cart->items = $cart->items ?? [];
        $cart->calculateTotals();
        $cart->save();

        return response()->json([
            'data' => $cart,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Add item to cart (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function addItem(Request $request, string $storeSlug): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        $store = $this->resolveStore($storeSlug);
        $sessionId = $this->resolveSessionId($request);

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'tenant_id' => $store->tenant_id,
                'store_id' => $store->id,
                'session_id' => $sessionId,
                'items' => [],
                'currency' => $store->settings['currency'] ?? 'USD',
            ]);
        }

        $product = Product::findOrFail($validated['product_id']);
        if ($product->tenant_id !== $store->tenant_id || !$product->is_active) {
            return response()->json(['message' => 'Product not available.'], 404);
        }

        $sync = ProductSync::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->where('is_synced', true)
            ->first();

        if (!$sync) {
            return response()->json(['message' => 'Product not available in store.'], 404);
        }

        $available = (int) ($product->quantity ?? 0);
        $items = $cart->items ?? [];
        $matchedIndex = null;

        foreach ($items as $index => $item) {
            if (($item['product_id'] ?? null) === $product->id &&
                ($item['variant_id'] ?? null) === ($validated['variant_id'] ?? null)) {
                $matchedIndex = $index;
                break;
            }
        }

        $incomingQuantity = (int) $validated['quantity'];
        if ($matchedIndex !== null) {
            $current = (int) ($items[$matchedIndex]['quantity'] ?? 0);
            if ($current + $incomingQuantity > $available) {
                return response()->json(['message' => 'Requested quantity exceeds available stock.'], 400);
            }
            $items[$matchedIndex]['quantity'] = $current + $incomingQuantity;
        } else {
            if ($incomingQuantity > $available) {
                return response()->json(['message' => 'Requested quantity exceeds available stock.'], 400);
            }

            $price = $sync->ecommerce_price ?? $product->price ?? 0;
            $image = $sync->ecommerce_images ?? null;

            $items[] = [
                'product_id' => $product->id,
                'variant_id' => $validated['variant_id'] ?? null,
                'product_name' => $product->name,
                'name' => $product->name,
                'product_sku' => $product->sku,
                'price' => (float) $price,
                'quantity' => $incomingQuantity,
                'product_image' => $image,
                'image' => $image,
            ];
        }

        $cart->items = array_values($items);
        $cart->calculateTotals();
        $cart->save();

        return response()->json([
            'message' => 'Item added to cart.',
            'data' => $cart,
            'session_id' => $sessionId,
        ], 201);
    }

    /**
     * Update cart item quantity (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @param  int  $itemIndex
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(Request $request, string $storeSlug, int $itemIndex): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $store = $this->resolveStore($storeSlug);
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');
        if (!$sessionId) {
            return response()->json(['message' => 'Session ID is required.'], 400);
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$cart || empty($cart->items) || !isset($cart->items[$itemIndex])) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $items = $cart->items;
        $item = $items[$itemIndex];
        $productId = $item['product_id'] ?? null;
        if (!$productId) {
            return response()->json(['message' => 'Invalid cart item.'], 400);
        }

        $product = Product::find($productId);
        if (!$product || $product->tenant_id !== $store->tenant_id || !$product->is_active) {
            return response()->json(['message' => 'Product not available.'], 404);
        }

        $available = (int) ($product->quantity ?? 0);
        $newQuantity = (int) $validated['quantity'];
        if ($newQuantity > $available) {
            return response()->json(['message' => 'Requested quantity exceeds available stock.'], 400);
        }

        $items[$itemIndex]['quantity'] = $newQuantity;
        $cart->items = array_values($items);
        $cart->calculateTotals();
        $cart->save();

        return response()->json([
            'message' => 'Cart updated.',
            'data' => $cart,
        ]);
    }

    /**
     * Remove item from cart (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @param  int  $itemIndex
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem(Request $request, string $storeSlug, int $itemIndex): JsonResponse
    {
        $store = $this->resolveStore($storeSlug);
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');
        if (!$sessionId) {
            return response()->json(['message' => 'Session ID is required.'], 400);
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$cart || empty($cart->items) || !isset($cart->items[$itemIndex])) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $items = $cart->items;
        array_splice($items, $itemIndex, 1);
        $cart->items = array_values($items);
        $cart->calculateTotals();
        $cart->save();

        return response()->json([
            'message' => 'Item removed.',
            'data' => $cart,
        ]);
    }

    private function resolveStore(string $storeSlug): Store
    {
        return Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function resolveSessionId(Request $request): string
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');
        return $sessionId ?: Str::uuid()->toString();
    }
}
