<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\InventorySerial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventorySerialController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventorySerial::class);

        $query = InventorySerial::with(['product', 'warehouse', 'batch'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('serial_number')) {
            $query->where('serial_number', 'like', '%'.$request->input('serial_number').'%');
        }

        $serials = $query->latest()->paginate();

        return \App\Modules\ERP\Http\Resources\InventorySerialResource::collection($serials);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', InventorySerial::class);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'batch_id' => 'nullable|exists:inventory_batches,id',
            'serial_number' => 'required|string|max:255',
            'status' => 'required|string|in:available,reserved,sold,returned,damaged',
            'manufacturing_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufacturing_date',
            'notes' => 'nullable|string',
        ]);

        // Check if serial number already exists
        $existing = InventorySerial::where('tenant_id', $request->user()->tenant_id)
            ->where('product_id', $validated['product_id'])
            ->where('serial_number', $validated['serial_number'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Serial number already exists for this product.',
            ], 422);
        }

        $validated['tenant_id'] = $request->user()->tenant_id;

        $serial = InventorySerial::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory serial created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\InventorySerialResource($serial->load(['product', 'warehouse'])),
        ], 201);
    }

    public function show(InventorySerial $inventorySerial): JsonResponse
    {
        $this->authorize('view', $inventorySerial);

        $inventorySerial->load(['product', 'warehouse', 'batch', 'transaction']);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\InventorySerialResource($inventorySerial),
        ]);
    }

    public function update(Request $request, InventorySerial $inventorySerial): JsonResponse
    {
        $this->authorize('update', $inventorySerial);

        $validated = $request->validate([
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'batch_id' => 'nullable|exists:inventory_batches,id',
            'status' => 'sometimes|required|string|in:available,reserved,sold,returned,damaged',
            'manufacturing_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufacturing_date',
            'notes' => 'nullable|string',
        ]);

        $inventorySerial->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory serial updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\InventorySerialResource($inventorySerial->fresh()->load(['product', 'warehouse'])),
        ]);
    }

    public function destroy(InventorySerial $inventorySerial): JsonResponse
    {
        $this->authorize('delete', $inventorySerial);

        $inventorySerial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory serial deleted successfully.',
        ]);
    }
}

