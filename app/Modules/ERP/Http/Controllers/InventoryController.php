<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\StoreInventoryTransactionRequest;
use App\Modules\ERP\Http\Resources\InventoryTransactionResource;
use App\Modules\ERP\Http\Resources\StockItemResource;
use App\Modules\ERP\Models\InventoryTransaction;
use App\Modules\ERP\Models\StockItem;
use App\Modules\ERP\Services\InventoryAvailabilityService;
use App\Modules\ERP\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryController extends Controller
{
    protected StockMovementService $stockMovementService;
    protected InventoryAvailabilityService $availabilityService;

    public function __construct(
        StockMovementService $stockMovementService,
        InventoryAvailabilityService $availabilityService
    ) {
        $this->stockMovementService = $stockMovementService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * List stock items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function stockItems(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StockItem::class);

        $query = StockItem::with(['product', 'warehouse', 'productVariant'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        $stockItems = $query->latest()->paginate();

        return StockItemResource::collection($stockItems);
    }

    /**
     * Get stock item details.
     *
     * @param  \App\Modules\ERP\Models\StockItem  $stockItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function stockItem(StockItem $stockItem): JsonResponse
    {
        $this->authorize('view', $stockItem);

        return response()->json([
            'data' => new StockItemResource($stockItem->load(['product', 'warehouse', 'productVariant'])),
        ]);
    }

    /**
     * Record inventory transaction.
     *
     * @param  \App\Modules\ERP\Http\Requests\StoreInventoryTransactionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordTransaction(StoreInventoryTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', InventoryTransaction::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        // If unit_of_measure is not provided, try to get it from the product
        if (empty($validated['unit_of_measure']) && !empty($validated['product_id'])) {
            $product = \App\Modules\ERP\Models\Product::find($validated['product_id']);
            if ($product && $product->unit_of_measure) {
                $validated['unit_of_measure'] = $product->unit_of_measure;
            }
        }

        $transaction = $this->stockMovementService->recordTransaction($validated);

        return response()->json([
            'message' => 'Inventory transaction recorded successfully.',
            'data' => new InventoryTransactionResource($transaction->load(['product', 'warehouse', 'batch'])),
        ], 201);
    }

    /**
     * List inventory transactions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function transactions(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventoryTransaction::class);

        $query = InventoryTransaction::with(['product', 'warehouse', 'batch', 'creator'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }

        $transactions = $query->latest('transaction_date')->paginate();

        return InventoryTransactionResource::collection($transactions);
    }

    /**
     * Check product availability.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
        ]);

        if ($request->has('warehouse_id')) {
            $availability = $this->availabilityService->checkWarehouseAvailability(
                $request->input('warehouse_id'),
                $request->input('product_id'),
                $request->input('quantity'),
                $request->input('product_variant_id')
            );
        } else {
            $availability = $this->availabilityService->checkAvailability(
                $request->input('product_id'),
                $request->input('quantity'),
                $request->input('product_variant_id')
            );
        }

        return response()->json(['data' => $availability]);
    }

    /**
     * Get low stock products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lowStock(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $threshold = $request->input('threshold', 10);
        $warehouseId = $request->input('warehouse_id');

        $lowStock = $this->availabilityService->getLowStockProducts($threshold, $warehouseId);

        return response()->json(['data' => $lowStock]);
    }
}

