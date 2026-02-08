<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\FiscalPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class FiscalPeriodController extends Controller
{
    /**
     * Display a listing of fiscal periods.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FiscalPeriod::class);

        $query = FiscalPeriod::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('fiscal_year_id')) {
            $query->where('fiscal_year_id', $request->input('fiscal_year_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $fiscalPeriods = $query->orderBy('start_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return \App\Modules\ERP\Http\Resources\FiscalPeriodResource::collection($fiscalPeriods);
    }

    /**
     * Store a newly created fiscal period.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', FiscalPeriod::class);

        $validated = $request->validate([
            'fiscal_year_id' => ['required', 'integer', Rule::exists('fiscal_years', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id))],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $fiscalPeriod = FiscalPeriod::create([
            'tenant_id' => $request->user()->tenant_id,
            ...$validated,
        ]);

        return response()->json([
            'data' => new \App\Modules\ERP\Http\Resources\FiscalPeriodResource($fiscalPeriod),
        ], 201);
    }

    /**
     * Display the specified fiscal period.
     */
    public function show(FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('view', $fiscalPeriod);

        return response()->json([
            'data' => new \App\Modules\ERP\Http\Resources\FiscalPeriodResource($fiscalPeriod),
        ]);
    }

    /**
     * Update the specified fiscal period.
     */
    public function update(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('update', $fiscalPeriod);

        $validated = $request->validate([
            'fiscal_year_id' => ['sometimes', 'integer', Rule::exists('fiscal_years', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id))],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $fiscalPeriod->update($validated);

        return response()->json([
            'data' => new \App\Modules\ERP\Http\Resources\FiscalPeriodResource($fiscalPeriod),
        ]);
    }

    /**
     * Delete the specified fiscal period.
     */
    public function destroy(FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $this->authorize('delete', $fiscalPeriod);

        $fiscalPeriod->delete();

        return response()->json([
            'message' => 'Fiscal period deleted successfully',
        ]);
    }
}

