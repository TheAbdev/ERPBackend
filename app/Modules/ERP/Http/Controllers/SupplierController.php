<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\StoreSupplierRequest;
use App\Modules\ERP\Http\Requests\UpdateSupplierRequest;
use App\Modules\ERP\Http\Resources\SupplierResource;
use App\Modules\ERP\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->with('creator');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // Filter by country
        if ($request->has('country')) {
            $query->where('country', $request->input('country'));
        }

        $suppliers = $query->latest()->paginate($request->input('per_page', 15));

        return SupplierResource::collection($suppliers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StoreSupplierRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create([
            ...$request->validated(),
            'tenant_id' => $request->user()->tenant_id,
            'created_by' => $request->user()->id,
        ]);

        $supplier->load('creator');

        return response()->json([
            'data' => new SupplierResource($supplier),
            'message' => 'Supplier created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $supplier->load('creator');

        return response()->json([
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\UpdateSupplierRequest  $request
     * @param  \App\Modules\ERP\Models\Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());

        $supplier->load('creator');

        return response()->json([
            'data' => new SupplierResource($supplier),
            'message' => 'Supplier updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\Supplier  $supplier
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}













