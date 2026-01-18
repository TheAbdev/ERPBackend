<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\VatReturnResource;
use App\Modules\ERP\Services\VatReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VATReportController extends Controller
{
    protected VatReportService $vatReportService;

    public function __construct(VatReportService $vatReportService)
    {
        $this->vatReportService = $vatReportService;
    }

    /**
     * Get VAT Return report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function vatReturn(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\Account::class);

        $validated = $request->validate([
            'fiscal_period_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
        ]);

        try {
            $vatReturn = $this->vatReportService->generateVatReturn(
                $validated['fiscal_period_id']
            );

            return response()->json([
                'data' => new VatReturnResource($vatReturn),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

