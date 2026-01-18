<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\UnitOfMeasureResource;
use App\Modules\ERP\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitOfMeasureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UnitOfMeasure::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name');

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $units = $query->get();

        return UnitOfMeasureResource::collection($units);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\UnitOfMeasure  $unitOfMeasure
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(UnitOfMeasure $unitOfMeasure): JsonResponse
    {
        return response()->json([
            'data' => new UnitOfMeasureResource($unitOfMeasure),
        ]);
    }
}

