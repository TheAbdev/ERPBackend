<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\ConfirmPurchaseOrderRequest;
use App\Modules\ERP\Http\Requests\ReceivePurchaseOrderRequest;
use App\Modules\ERP\Http\Requests\StorePurchaseOrderRequest;
use App\Modules\ERP\Http\Requests\UpdatePurchaseOrderRequest;
use App\Modules\ERP\Http\Resources\PurchaseOrderResource;
use App\Modules\ERP\Models\PurchaseOrder;
use App\Modules\ERP\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    protected PurchaseOrderService $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query=PurchaseOrder::with(['warehouse', 'currency', 'creator', 'items.product']);
        if ($request->has('search')) {
            $query->where('order_number', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $orders = $query->latest('order_date')->paginate();

        return PurchaseOrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StorePurchaseOrderRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['tenant_id'] = $request->user()->tenant_id;

        $order = PurchaseOrder::create($validated);

        // Create order items
        foreach ($request->input('items', []) as $itemData) {
            // If unit_of_measure is not provided, try to get it from the product
            if (empty($itemData['unit_of_measure']) && !empty($itemData['product_id'])) {
                $product = \App\Modules\ERP\Models\Product::find($itemData['product_id']);
                if ($product && $product->unit_of_measure) {
                    $itemData['unit_of_measure'] = $product->unit_of_measure;
                }
            }
            $order->items()->create($itemData);
        }

        // Calculate totals
        $this->purchaseOrderService->calculateTotals($order);

        // Dispatch entity created event
        event(new EntityCreated($order, $request->user()->id));

        return response()->json([
            'message' => 'Purchase order created successfully.',
            'data' => new PurchaseOrderResource($order->load(['warehouse', 'currency', 'items.product'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', $purchaseOrder);

        return response()->json([
            'data' => new PurchaseOrderResource($purchaseOrder->load(['warehouse', 'currency', 'creator', 'confirmer', 'items.product', 'items.productVariant'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\UpdatePurchaseOrderRequest  $request
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->isConfirmed()) {
            return response()->json([
                'message' => 'Cannot update a confirmed order.',
            ], 422);
        }

        $purchaseOrder->update($request->validated());

        // Update items if provided
        if ($request->has('items')) {
            $purchaseOrder->items()->delete();
            foreach ($request->input('items', []) as $itemData) {
                $purchaseOrder->items()->create($itemData);
            }
        }

        // Recalculate totals
        $this->purchaseOrderService->calculateTotals($purchaseOrder->fresh());

        // Dispatch entity updated event
        event(new EntityUpdated($purchaseOrder->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Purchase order updated successfully.',
            'data' => new PurchaseOrderResource($purchaseOrder->load(['warehouse', 'currency', 'items.product'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('delete', $purchaseOrder);

        if ($purchaseOrder->isConfirmed()) {
            return response()->json([
                'message' => 'Cannot delete a confirmed order. Cancel it first.',
            ], 422);
        }

        // Dispatch entity deleted event before deletion
        event(new EntityDeleted($purchaseOrder, request()->user()->id));

        $purchaseOrder->delete();

        return response()->json([
            'message' => 'Purchase order deleted successfully.',
        ]);
    }

    /**
     * Confirm a purchase order.
     *
     * @param  \App\Modules\ERP\Http\Requests\ConfirmPurchaseOrderRequest  $request
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(ConfirmPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);

        try {
            $this->purchaseOrderService->confirmOrder($purchaseOrder, $request->user()->id);

            return response()->json([
                'message' => 'Purchase order confirmed successfully.',
                'data' => new PurchaseOrderResource($purchaseOrder->fresh()->load(['warehouse', 'currency', 'items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a purchase order.
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);

        try {
            $this->purchaseOrderService->cancelOrder($purchaseOrder);

            return response()->json([
                'message' => 'Purchase order cancelled successfully.',
                'data' => new PurchaseOrderResource($purchaseOrder->fresh()->load(['warehouse', 'currency', 'items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Receive items from a purchase order.
     *
     * @param  \App\Modules\ERP\Http\Requests\ReceivePurchaseOrderRequest  $request
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $purchaseOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);

        try {
            $this->purchaseOrderService->receiveOrder(
                $purchaseOrder,
                $request->input('items'),
                $request->user()->id
            );

            return response()->json([
                'message' => 'Purchase order receipt processed successfully.',
                'data' => new PurchaseOrderResource($purchaseOrder->fresh()->load(['warehouse', 'currency', 'items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

