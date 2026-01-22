<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Cart;
use App\Modules\ECommerce\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Get or create cart (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCart(Request $request, string $storeSlug): JsonResponse
    {
        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id') ?? Str::uuid()->toString();
        
        // For public storefront, use session_id only (not user ID)
        // If user is logged in and has an ecommerce customer account, we can link it later
        $customerId = null;

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$cart) {
            $cart = Cart::withoutGlobalScopes()->create([
                'tenant_id' => $store->tenant_id,
                'store_id' => $store->id,
                'customer_id' => null, // Guest cart - no customer ID
                'session_id' => $sessionId,
                'items' => [],
                'currency' => $store->settings['currency'] ?? 'USD',
                'expires_at' => now()->addDays(7),
            ]);
        }

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
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'min:1'],
            'session_id' => ['nullable', 'string'],
        ]);

        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $sessionId = $request->header('X-Session-ID') ?? $validated['session_id'] ?? Str::uuid()->toString();
        
        // For public storefront, use session_id only
        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$cart) {
            $cart = Cart::withoutGlobalScopes()->create([
                'tenant_id' => $store->tenant_id,
                'store_id' => $store->id,
                'customer_id' => null, // Guest cart
                'session_id' => $sessionId,
                'items' => [],
                'currency' => $store->settings['currency'] ?? 'USD',
                'expires_at' => now()->addDays(7),
            ]);
        }

        $items = $cart->items ?? [];
        $existingIndex = null;

        foreach ($items as $index => $item) {
            if ($item['product_id'] == $validated['product_id'] && 
                ($item['variant_id'] ?? null) == ($validated['variant_id'] ?? null)) {
                $existingIndex = $index;
                break;
            }
        }

        // Get product price from ProductSync or default to 0
        $productSync = \App\Modules\ECommerce\Models\ProductSync::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('product_id', $validated['product_id'])
            ->where('store_visibility', true)
            ->where('is_synced', true)
            ->first();
        
        $productPrice = $productSync?->ecommerce_price ?? 0;
        
        if ($existingIndex !== null) {
            $items[$existingIndex]['quantity'] += $validated['quantity'];
        } else {
            $items[] = [
                'product_id' => $validated['product_id'],
                'variant_id' => $validated['variant_id'] ?? null,
                'quantity' => $validated['quantity'],
                'price' => $productPrice,
            ];
        }

        $cart->items = $items;
        $cart->calculateTotals();
        $cart->save();

        return response()->json([
            'message' => 'Item added to cart.',
            'data' => $cart,
            'session_id' => $sessionId, // Return session_id so frontend can save it
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
        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');
        
        if (!$sessionId) {
            return response()->json([
                'message' => 'Session ID is required.',
            ], 400);
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $items = $cart->items ?? [];
        if (isset($items[$itemIndex])) {
            unset($items[$itemIndex]);
            $cart->items = array_values($items);
            $cart->calculateTotals();
            $cart->save();
        }

        return response()->json([
            'message' => 'Item removed from cart.',
            'data' => $cart,
        ]);
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
            'quantity' => ['required', 'numeric', 'min:1'],
        ]);

        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');
        
        if (!$sessionId) {
            return response()->json([
                'message' => 'Session ID is required.',
            ], 400);
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $items = $cart->items ?? [];
        if (isset($items[$itemIndex])) {
            $items[$itemIndex]['quantity'] = $validated['quantity'];
            $cart->items = $items;
            $cart->calculateTotals();
            $cart->save();
        }

        return response()->json([
            'message' => 'Cart item updated.',
            'data' => $cart,
        ]);
    }
}

