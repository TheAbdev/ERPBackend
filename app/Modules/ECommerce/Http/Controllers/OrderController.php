<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\Order;
use App\Modules\ECommerce\Models\OrderItem;
use App\Modules\ECommerce\Models\Cart;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ECommerce\Models\Customer;
use App\Modules\ECommerce\Services\OrderSyncService;
use App\Modules\ERP\Services\PaymentGatewayService;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected OrderSyncService $orderSyncService;
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(
        OrderSyncService $orderSyncService,
        PaymentGatewayService $paymentGatewayService
    ) {
        $this->orderSyncService = $orderSyncService;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Display a listing of orders.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['customer', 'items.product', 'store'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Create order from cart (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFromCart(Request $request, string $storeSlug): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'exists:ecommerce_carts,id'],
            'billing_address' => ['required', 'array'],
            'shipping_address' => ['required', 'array'],
            'payment_method' => ['required', 'string'],
            'shipping_method' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $cart = Cart::withoutGlobalScopes()
            ->where('id', $validated['cart_id'])
            ->where('store_id', $store->id)
            ->with(['store'])
            ->firstOrFail();

        if (empty($cart->items)) {
            return response()->json([
                'message' => 'Cart is empty.',
            ], 400);
        }

        // Create or find customer from billing address
        $customerId = $cart->customer_id;
        if (!$customerId && !empty($validated['billing_address']['email'])) {
            // Find existing customer by email or create new one
            $customer = Customer::firstOrCreate(
                [
                    'tenant_id' => $store->tenant_id,
                    'store_id' => $store->id,
                    'email' => $validated['billing_address']['email'],
                ],
                [
                    'name' => $validated['billing_address']['name'] ?? 'Guest Customer',
                    'phone' => $validated['billing_address']['phone'] ?? null,
                    'addresses' => [
                        'billing' => $validated['billing_address'],
                        'shipping' => $validated['shipping_address'],
                    ],
                    'is_active' => true,
                ]
            );
            $customerId = $customer->id;
        }

        $order = Order::create([
            'tenant_id' => $store->tenant_id,
            'store_id' => $store->id,
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $customerId,
            'session_id' => $cart->session_id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $cart->subtotal,
            'tax' => $cart->tax,
            'shipping' => $cart->shipping,
            'total' => $cart->total,
            'currency' => $cart->currency,
            'billing_address' => $validated['billing_address'],
            'shipping_address' => $validated['shipping_address'],
            'payment_method' => $validated['payment_method'],
            'shipping_method' => $validated['shipping_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create order items
        foreach ($cart->items as $item) {
            $product = \App\Modules\ERP\Models\Product::find($item['product_id']);

            OrderItem::create([
                'tenant_id' => $order->tenant_id,
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total' => $item['price'] * $item['quantity'],
            ]);
        }

        // Convert to ERP Sales Order
        $salesOrder = $this->orderSyncService->convertToSalesOrder($order, $store->tenant_id);

        // Dispatch entity created event
        event(new EntityCreated($order, null));

        // Delete cart
        $cart->delete();

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order->load(['items.product', 'customer']),
            'sales_order_id' => $salesOrder->id,
        ], 201);
    }

    /**
     * Display the specified order.
     *
     * @param  \App\Modules\ECommerce\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'data' => $order->load(['items.product', 'customer', 'store', 'salesOrder']),
        ]);
    }

    /**
     * Display a storefront order (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPublicOrder(Request $request, string $storeSlug, int $orderId): JsonResponse
    {
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

        $order = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->where('id', $orderId)
            ->firstOrFail();

        return response()->json([
            'data' => $order->load(['items.product', 'customer', 'store']),
        ]);
    }

    /**
     * Display storefront orders for a session (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPublicOrders(Request $request, string $storeSlug): JsonResponse
    {
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

        $orders = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('session_id', $sessionId)
            ->with(['items.product', 'customer', 'store'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Update the specified order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ECommerce\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:pending,processing,shipped,delivered,cancelled,refunded'],
            'payment_status' => ['sometimes', 'string', 'in:pending,paid,failed,refunded'],
            'notes' => ['nullable', 'string'],
        ]);

        $order->update($validated);

        // Dispatch entity updated event
        event(new EntityUpdated($order, $request->user()->id ?? null));

        return response()->json([
            'message' => 'Order updated successfully.',
            'data' => $order->load(['items.product', 'customer']),
        ]);
    }

    /**
     * Process payment for ecommerce order (public).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $storeSlug
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request, string $storeSlug, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'payment_gateway_id' => ['required', 'exists:payment_gateways,id'],
            'payment_data' => ['sometimes', 'array'], // Gateway-specific payment data
        ]);

        // Remove tenant scope for public storefront access
        $store = Store::withoutGlobalScopes()
            ->where('slug', $storeSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $order = Order::where('id', $orderId)
            ->where('store_id', $store->id)
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Order is already paid.',
            ], 400);
        }

        $gateway = PaymentGateway::where('id', $validated['payment_gateway_id'])
            ->where('tenant_id', $store->tenant_id)
            ->where('is_active', true)
            ->firstOrFail();

        try {
            return DB::transaction(function () use ($order, $gateway, $validated) {
                // Get currency
                $currency = \App\Modules\ERP\Models\Currency::where('code', $order->currency)->first();
                if (!$currency) {
                    throw new \Exception("Currency {$order->currency} not found.");
                }

                // Create payment record
                $payment = Payment::create([
                    'tenant_id' => $order->tenant_id,
                    'type' => 'incoming', // E-commerce payments are incoming
                    'payment_date' => now(),
                    'amount' => $order->total,
                    'currency_id' => $currency->id,
                    'payment_method' => $order->payment_method,
                    'reference_number' => $order->order_number,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'notes' => "Payment for ecommerce order: {$order->order_number}",
                ]);

                // Process payment through gateway
                $result = $this->paymentGatewayService->processPayment(
                    $payment,
                    $gateway,
                    $validated['payment_data'] ?? []
                );

                // Update order payment status based on gateway result
                $gatewayStatus = $result['status'] ?? 'pending';
                if ($gatewayStatus === 'completed' || $gatewayStatus === 'success' || $gatewayStatus === 'paid') {
                    $order->payment_status = 'paid';
                    $order->save();
                } elseif ($gatewayStatus === 'failed') {
                    $order->payment_status = 'failed';
                    $order->save();
                }

                return response()->json([
                    'message' => 'Payment processed successfully.',
                    'data' => [
                        'payment_id' => $payment->id,
                        'order_id' => $order->id,
                        'status' => $result['status'],
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ], 422);
        }
    }
}

