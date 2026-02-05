<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\ConfirmSalesOrderRequest;
use App\Modules\ERP\Http\Requests\DeliverSalesOrderRequest;
use App\Modules\ERP\Http\Requests\StoreSalesOrderRequest;
use App\Modules\ERP\Http\Requests\UpdateSalesOrderRequest;
use App\Modules\ERP\Http\Resources\SalesOrderResource;
use App\Modules\ERP\Models\SalesOrder;
use App\Modules\ERP\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SalesOrderController extends Controller
{
    protected SalesOrderService $salesOrderService;

    public function __construct(SalesOrderService $salesOrderService)
    {
        $this->salesOrderService = $salesOrderService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalesOrder::class);

        $query = SalesOrder::with(['warehouse', 'currency', 'creator', 'items.product']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest('order_date')->paginate();

        return SalesOrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StoreSalesOrderRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $this->authorize('create', SalesOrder::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['tenant_id'] = $request->user()->tenant_id;

        $order = SalesOrder::create($validated);

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
        $this->salesOrderService->calculateTotals($order);

        // Dispatch entity created event
        event(new EntityCreated($order, $request->user()->id));

        return response()->json([
            'message' => 'Sales order created successfully.',
            'data' => new SalesOrderResource($order->load(['warehouse', 'currency', 'items.product'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('view', $salesOrder);

        return response()->json([
            'data' => new SalesOrderResource($salesOrder->load(['warehouse', 'currency', 'creator', 'confirmer', 'items.product', 'items.productVariant'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\UpdateSalesOrderRequest  $request
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        if ($salesOrder->isConfirmed()) {
            return response()->json([
                'message' => 'Cannot update a confirmed order.',
            ], 422);
        }

        $salesOrder->update($request->validated());

        // Update items if provided
        if ($request->has('items')) {
            $salesOrder->items()->delete();
            foreach ($request->input('items', []) as $itemData) {
                // If unit_of_measure is not provided, try to get it from the product
                if (empty($itemData['unit_of_measure']) && !empty($itemData['product_id'])) {
                    $product = \App\Modules\ERP\Models\Product::find($itemData['product_id']);
                    if ($product && $product->unit_of_measure) {
                        $itemData['unit_of_measure'] = $product->unit_of_measure;
                    }
                }
                $salesOrder->items()->create($itemData);
            }
        }

        // Recalculate totals
        $this->salesOrderService->calculateTotals($salesOrder->fresh());

        // Dispatch entity updated event
        event(new EntityUpdated($salesOrder->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Sales order updated successfully.',
            'data' => new SalesOrderResource($salesOrder->load(['warehouse', 'currency', 'items.product'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('delete', $salesOrder);

        if ($salesOrder->isConfirmed()) {
            return response()->json([
                'message' => 'Cannot delete a confirmed order. Cancel it first.',
            ], 422);
        }

        // Dispatch entity deleted event before deletion
        event(new EntityDeleted($salesOrder, request()->user()->id));

        $salesOrder->delete();

        return response()->json([
            'message' => 'Sales order deleted successfully.',
        ]);
    }

    /**
     * Confirm a sales order.
     *
     * @param  \App\Modules\ERP\Http\Requests\ConfirmSalesOrderRequest  $request
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(ConfirmSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        try {
            $this->salesOrderService->confirmOrder($salesOrder, $request->user()->id);

            return response()->json([
                'message' => 'Sales order confirmed successfully.',
                'data' => new SalesOrderResource($salesOrder->fresh()->load(['warehouse', 'currency', 'items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a sales order.
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        try {
            $this->salesOrderService->cancelOrder($salesOrder);

            return response()->json([
                'message' => 'Sales order cancelled successfully.',
                'data' => new SalesOrderResource($salesOrder->fresh()->load(['warehouse', 'currency', 'items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Deliver items from a sales order.
     *
     * @param  \App\Modules\ERP\Http\Requests\DeliverSalesOrderRequest  $request
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliver(DeliverSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        try {
            $this->salesOrderService->deliverOrder(
                $salesOrder,
                $request->input('items'),
                $request->user()->id
            );

            return response()->json([
                'message' => 'Sales order delivery processed successfully.',
                'data' => new SalesOrderResource($salesOrder->fresh()->load(['warehouse', 'currency', 'items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Partially deliver items from a sales order.
     *
     * @param  \App\Modules\ERP\Http\Requests\DeliverSalesOrderRequest  $request
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @return \Illuminate\Http\JsonResponse
     */
    public function partialDeliver(DeliverSalesOrderRequest $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize('update', $salesOrder);

        if (! $salesOrder->isConfirmed() && $salesOrder->status !== 'partially_delivered') {
            return response()->json([
                'message' => 'Only confirmed or partially delivered orders can have partial deliveries.',
            ], 422);
        }

        try {
            $this->salesOrderService->deliverOrder(
                $salesOrder,
                $request->input('items'),
                $request->user()->id
            );

            return response()->json([
                'message' => 'Partial delivery processed successfully.',
                'data' => new SalesOrderResource($salesOrder->fresh()->load(['warehouse', 'currency', 'items.product'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

