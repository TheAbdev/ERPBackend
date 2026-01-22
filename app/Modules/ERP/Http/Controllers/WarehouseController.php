<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\WarehouseResource;
use App\Modules\ERP\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Warehouse::class);

        $query = Warehouse::query()
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name, code, or address
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $warehouses = $query->latest()->paginate();

        return WarehouseResource::collection($warehouses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'allow_negative_stock' => ['boolean'],
        ]);

        $validated['tenant_id'] = $tenantId;

        $warehouse = Warehouse::create($validated);

        // Dispatch entity created event
        event(new EntityCreated($warehouse, $request->user()->id));

        return response()->json([
            'message' => 'Warehouse created successfully.',
            'data' => new WarehouseResource($warehouse),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('view', $warehouse);

        return response()->json([
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorize('update', $warehouse);

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($warehouse->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'allow_negative_stock' => ['boolean'],
        ]);

        $warehouse->update($validated);

        // Dispatch entity updated event
        event(new EntityUpdated($warehouse->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Warehouse updated successfully.',
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorize('delete', $warehouse);

        // Dispatch entity deleted event before deletion
        event(new EntityDeleted($warehouse, request()->user()->id));

        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse deleted successfully.',
        ]);
    }
}







