<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\CurrencyResource;
use App\Modules\ERP\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Currency::class);

        $query = Currency::query()
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        } else {
            // Default: show active only if not specified
            $query->where('is_active', true);
        }

        // Search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $currencies = $query->orderBy('is_base_currency', 'desc')
            ->orderBy('code')
            ->paginate($request->input('per_page', 15));

        return CurrencyResource::collection($currencies);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Currency::class);

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'size:3',
                'uppercase',
                Rule::unique('currencies', 'code')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:8'],
            'is_base_currency' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // If this is set as base currency, unset other base currencies
        if ($validated['is_base_currency'] ?? false) {
            Currency::where('tenant_id', $tenantId)
                ->where('is_base_currency', true)
                ->update(['is_base_currency' => false]);
        }

        $validated['tenant_id'] = $tenantId;
        $validated['decimal_places'] = $validated['decimal_places'] ?? 2;

        $currency = Currency::create($validated);

        return response()->json([
            'message' => 'Currency created successfully.',
            'data' => new CurrencyResource($currency),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\Currency  $currency
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Currency $currency): JsonResponse
    {
        $this->authorize('view', $currency);

        return response()->json([
            'data' => new CurrencyResource($currency),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Currency  $currency
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Currency $currency): JsonResponse
    {
        $this->authorize('update', $currency);

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'code' => [
                'sometimes',
                'required',
                'string',
                'size:3',
                'uppercase',
                Rule::unique('currencies', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($currency->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:8'],
            'is_base_currency' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // If this is set as base currency, unset other base currencies
        if (isset($validated['is_base_currency']) && $validated['is_base_currency']) {
            Currency::where('tenant_id', $tenantId)
                ->where('is_base_currency', true)
                ->where('id', '!=', $currency->id)
                ->update(['is_base_currency' => false]);
        }

        $currency->update($validated);

        return response()->json([
            'message' => 'Currency updated successfully.',
            'data' => new CurrencyResource($currency),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\Currency  $currency
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Currency $currency): JsonResponse
    {
        $this->authorize('delete', $currency);

        // Prevent deletion of base currency
        if ($currency->is_base_currency) {
            return response()->json([
                'message' => 'Cannot delete the base currency.',
            ], 422);
        }

        $currency->delete();

        return response()->json([
            'message' => 'Currency deleted successfully.',
        ]);
    }
}

