<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\TaxRateResource;
use App\Modules\ERP\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TaxRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TaxRate::class);

        $query = TaxRate::with('account')
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $taxRates = $query->orderBy('code')->paginate();

        return TaxRateResource::collection($taxRates);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', TaxRate::class);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tax_rates')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'type' => ['required', 'string', Rule::in(['sales', 'purchase', 'both'])],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $taxRate = TaxRate::create($validated);

        return response()->json([
            'message' => 'Tax rate created successfully.',
            'data' => new TaxRateResource($taxRate->load('account')),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\TaxRate  $taxRate
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('view', $taxRate);

        $taxRate->load('account');

        return response()->json([
            'data' => new TaxRateResource($taxRate),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\TaxRate  $taxRate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, TaxRate $taxRate): JsonResponse
    {
        $this->authorize('update', $taxRate);

        $validated = $request->validate([
            'code' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tax_rates')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id))->ignore($taxRate->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['sales', 'purchase', 'both'])],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $taxRate->update($validated);

        return response()->json([
            'message' => 'Tax rate updated successfully.',
            'data' => new TaxRateResource($taxRate->fresh()->load('account')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\TaxRate  $taxRate
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $this->authorize('delete', $taxRate);

        // Check if tax rate is used in any invoices
        if ($taxRate->salesInvoiceItems()->exists() || $taxRate->purchaseInvoiceItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete tax rate that is used in invoices.',
            ], 422);
        }

        $taxRate->delete();

        return response()->json([
            'message' => 'Tax rate deleted successfully.',
        ]);
    }
}

