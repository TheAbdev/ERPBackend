<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\AssetRegisterResource;
use App\Modules\ERP\Http\Resources\DepreciationScheduleResource;
use App\Modules\ERP\Services\AssetReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetReportController extends Controller
{
    protected AssetReportService $assetReportService;

    public function __construct(AssetReportService $assetReportService)
    {
        $this->assetReportService = $assetReportService;
    }

    /**
     * Get Fixed Asset Register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assetRegister(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\FixedAsset::class);

        $validated = $request->validate([
            'fiscal_period_id' => [
                'nullable',
                'integer',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
        ]);

        try {
            $register = $this->assetReportService->generateAssetRegister(
                $validated['fiscal_period_id'] ?? null
            );

            return response()->json([
                'data' => new AssetRegisterResource($register),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Depreciation Schedule for an asset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $assetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function depreciationSchedule(Request $request, int $assetId): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\FixedAsset::class);

        try {
            $schedule = $this->assetReportService->generateDepreciationSchedule($assetId);

            return response()->json([
                'data' => new DepreciationScheduleResource($schedule),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Accumulated Depreciation Summary.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function accumulatedDepreciationSummary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\FixedAsset::class);

        $validated = $request->validate([
            'fiscal_period_id' => [
                'nullable',
                'integer',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
        ]);

        try {
            $summary = $this->assetReportService->generateAccumulatedDepreciationSummary(
                $validated['fiscal_period_id'] ?? null
            );

            return response()->json([
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}




