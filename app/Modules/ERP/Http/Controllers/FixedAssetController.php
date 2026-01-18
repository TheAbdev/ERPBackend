<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\FixedAssetResource;
use App\Modules\ERP\Models\FixedAsset;
use App\Modules\ERP\Services\AccountingService;
use App\Modules\ERP\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FixedAssetController extends Controller
{
    protected AssetService $assetService;
    protected AccountingService $accountingService;

    public function __construct(
        AssetService $assetService,
        AccountingService $accountingService
    ) {
        $this->assetService = $assetService;
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FixedAsset::class);

        $query = FixedAsset::with(['assetAccount', 'depreciationExpenseAccount', 'accumulatedDepreciationAccount', 'currency', 'fiscalYear', 'fiscalPeriod', 'creator'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('fiscal_period_id')) {
            $query->where('fiscal_period_id', $request->input('fiscal_period_id'));
        }

        $assets = $query->orderBy('asset_code')->paginate();

        return FixedAssetResource::collection($assets);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', FixedAsset::class);

        $validated = $request->validate([
            'asset_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fixed_assets')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'acquisition_date' => ['required', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'depreciation_method' => ['sometimes', 'string', Rule::in(['straight_line'])],
            'asset_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'depreciation_expense_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'accumulated_depreciation_account_id' => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'draft';
        $validated['depreciation_method'] = $validated['depreciation_method'] ?? 'straight_line';
        $validated['salvage_value'] = $validated['salvage_value'] ?? 0;

        // Set fiscal period from acquisition date
        $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($validated['acquisition_date']);
        $validated['fiscal_year_id'] = $fiscalPeriod->fiscal_year_id;
        $validated['fiscal_period_id'] = $fiscalPeriod->id;

        $asset = FixedAsset::create($validated);

        return response()->json([
            'message' => 'Fixed asset created successfully.',
            'data' => new FixedAssetResource($asset->load(['assetAccount', 'depreciationExpenseAccount', 'accumulatedDepreciationAccount', 'currency', 'fiscalYear', 'fiscalPeriod'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(FixedAsset $fixedAsset): JsonResponse
    {
        $this->authorize('view', $fixedAsset);

        $fixedAsset->load([
            'assetAccount',
            'depreciationExpenseAccount',
            'accumulatedDepreciationAccount',
            'currency',
            'fiscalYear',
            'fiscalPeriod',
            'creator',
            'activator',
            'disposer',
            'depreciations.fiscalPeriod',
            'depreciations.journalEntry',
        ]);

        return response()->json([
            'data' => new FixedAssetResource($fixedAsset),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, FixedAsset $fixedAsset): JsonResponse
    {
        $this->authorize('update', $fixedAsset);

        if (!$fixedAsset->isDraft()) {
            return response()->json([
                'message' => 'Cannot update asset. Only draft assets can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'asset_code' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('fixed_assets')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id))->ignore($fixedAsset->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'acquisition_date' => ['sometimes', 'required', 'date'],
            'acquisition_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['sometimes', 'required', 'integer', 'min:1'],
            'depreciation_method' => ['sometimes', 'string', Rule::in(['straight_line'])],
            'asset_account_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'depreciation_expense_account_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'accumulated_depreciation_account_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'currency_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('currencies', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'notes' => ['nullable', 'string'],
        ]);

        $fixedAsset->update($validated);

        return response()->json([
            'message' => 'Fixed asset updated successfully.',
            'data' => new FixedAssetResource($fixedAsset->fresh()->load(['assetAccount', 'depreciationExpenseAccount', 'accumulatedDepreciationAccount', 'currency'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(FixedAsset $fixedAsset): JsonResponse
    {
        $this->authorize('delete', $fixedAsset);

        if (!$fixedAsset->isDraft()) {
            return response()->json([
                'message' => 'Cannot delete asset. Only draft assets can be deleted.',
            ], 422);
        }

        $fixedAsset->delete();

        return response()->json([
            'message' => 'Fixed asset deleted successfully.',
        ]);
    }

    /**
     * Activate the asset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(Request $request, FixedAsset $fixedAsset): JsonResponse
    {
        $this->authorize('update', $fixedAsset);

        $validated = $request->validate([
            'credit_account_code' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->assetService->activateAsset(
                $fixedAsset,
                $request->user()->id,
                $validated['credit_account_code'] ?? 'AP'
            );

            return response()->json([
                'message' => 'Asset activated successfully.',
                'data' => new FixedAssetResource($fixedAsset->fresh()->load(['assetAccount', 'fiscalYear', 'fiscalPeriod', 'activator'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Dispose the asset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\JsonResponse
     */
    public function dispose(Request $request, FixedAsset $fixedAsset): JsonResponse
    {
        $this->authorize('update', $fixedAsset);

        $validated = $request->validate([
            'disposal_amount' => ['required', 'numeric', 'min:0'],
            'disposal_date' => ['required', 'date'],
        ]);

        try {
            $this->assetService->disposeAsset(
                $fixedAsset,
                $validated['disposal_amount'],
                $validated['disposal_date'],
                $request->user()->id
            );

            return response()->json([
                'message' => 'Asset disposed successfully.',
                'data' => new FixedAssetResource($fixedAsset->fresh()->load(['assetAccount', 'disposer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}




