<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\ReorderRule;
use App\Modules\ERP\Services\ReorderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReorderRuleController extends Controller
{
    protected ReorderService $reorderService;

    public function __construct(ReorderService $reorderService)
    {
        $this->reorderService = $reorderService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ReorderRule::class);

        $query = ReorderRule::with(['product', 'warehouse', 'supplier'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->latest()->paginate();

        return \App\Modules\ERP\Http\Resources\ReorderRuleResource::collection($rules);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ReorderRule::class);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'reorder_point' => 'required|numeric|min:0',
            'reorder_quantity' => 'required|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:accounts,id',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = $request->input('is_active', true);

        $rule = ReorderRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reorder rule created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ReorderRuleResource($rule->load(['product', 'warehouse', 'supplier'])),
        ], 201);
    }

    public function show(ReorderRule $reorderRule): JsonResponse
    {
        $this->authorize('view', $reorderRule);

        $reorderRule->load(['product', 'warehouse', 'supplier']);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\ReorderRuleResource($reorderRule),
        ]);
    }

    public function update(Request $request, ReorderRule $reorderRule): JsonResponse
    {
        $this->authorize('update', $reorderRule);

        $validated = $request->validate([
            'reorder_point' => 'sometimes|required|numeric|min:0',
            'reorder_quantity' => 'sometimes|required|numeric|min:0',
            'maximum_stock' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:accounts,id',
            'is_active' => 'boolean',
        ]);

        $reorderRule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reorder rule updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ReorderRuleResource($reorderRule->fresh()->load(['product', 'warehouse', 'supplier'])),
        ]);
    }

    public function destroy(ReorderRule $reorderRule): JsonResponse
    {
        $this->authorize('delete', $reorderRule);

        $reorderRule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reorder rule deleted successfully.',
        ]);
    }

    public function checkAndReorder(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReorderRule::class);

        $ordersCreated = $this->reorderService->checkAndReorder();

        return response()->json([
            'success' => true,
            'message' => 'Reorder check completed.',
            'data' => [
                'orders_created' => count($ordersCreated),
                'orders' => $ordersCreated,
            ],
        ]);
    }
}

