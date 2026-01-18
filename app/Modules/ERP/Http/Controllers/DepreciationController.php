<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\AssetDepreciationResource;
use App\Modules\ERP\Http\Resources\DepreciationScheduleResource;
use App\Modules\ERP\Models\FixedAsset;
use App\Modules\ERP\Services\DepreciationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepreciationController extends Controller
{
    protected DepreciationService $depreciationService;

    public function __construct(DepreciationService $depreciationService)
    {
        $this->depreciationService = $depreciationService;
    }

    /**
     * Generate depreciation schedule for an asset.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\JsonResponse
     */
    public function schedule(FixedAsset $fixedAsset): JsonResponse
    {
        $this->authorize('view', $fixedAsset);

        try {
            $schedule = $this->depreciationService->generateDepreciationSchedule($fixedAsset);

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
     * Post depreciation for a fiscal period.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postForPeriod(Request $request): JsonResponse
    {
        $this->authorize('create', \App\Modules\ERP\Models\AssetDepreciation::class);

        $validated = $request->validate([
            'fiscal_period_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
        ]);

        try {
            $result = $this->depreciationService->postDepreciationForPeriod(
                $validated['fiscal_period_id'],
                $request->user()->id
            );

            return response()->json([
                'message' => "Depreciation posted for {$result['posted_count']} asset(s).",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get depreciations for an asset.
     *
     * @param  \App\Modules\ERP\Models\FixedAsset  $fixedAsset
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(FixedAsset $fixedAsset): JsonResponse
    {
        $this->authorize('view', $fixedAsset);

        $depreciations = $fixedAsset->depreciations()
            ->with(['fiscalPeriod', 'fiscalYear', 'journalEntry', 'poster'])
            ->orderBy('depreciation_date')
            ->get();

        return response()->json([
            'data' => AssetDepreciationResource::collection($depreciations),
        ]);
    }
}




