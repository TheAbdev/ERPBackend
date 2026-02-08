<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\FiscalYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalYearController extends Controller
{
    /**
     * Display a listing of fiscal years.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FiscalYear::class);

        $query = FiscalYear::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $fiscalYears = $query->orderBy('start_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return \App\Modules\ERP\Http\Resources\FiscalYearResource::collection($fiscalYears);
    }

    /**
     * Store a newly created fiscal year.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', FiscalYear::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $fiscalYear = FiscalYear::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$validated,
        ]);

        return response()->json([
            'data' => new \App\Modules\ERP\Http\Resources\FiscalYearResource($fiscalYear),
        ], 201);
    }

    /**
     * Display the specified fiscal year.
     */
    public function show(FiscalYear $fiscalYear): JsonResponse
    {
        $this->authorize('view', $fiscalYear);

        return response()->json([
            'data' => new \App\Modules\ERP\Http\Resources\FiscalYearResource($fiscalYear),
        ]);
    }

    /**
     * Update the specified fiscal year.
     */
    public function update(Request $request, FiscalYear $fiscalYear): JsonResponse
    {
        $this->authorize('update', $fiscalYear);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $fiscalYear->update($validated);

        return response()->json([
            'data' => new \App\Modules\ERP\Http\Resources\FiscalYearResource($fiscalYear),
        ]);
    }

    /**
     * Delete the specified fiscal year.
     */
    public function destroy(FiscalYear $fiscalYear): JsonResponse
    {
        $this->authorize('delete', $fiscalYear);

        $fiscalYear->delete();

        return response()->json([
            'message' => 'Fiscal year deleted successfully',
        ]);
    }
}

