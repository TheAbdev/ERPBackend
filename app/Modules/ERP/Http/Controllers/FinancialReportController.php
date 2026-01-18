<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\BalanceSheetResource;
use App\Modules\ERP\Http\Resources\GeneralLedgerResource;
use App\Modules\ERP\Http\Resources\ProfitLossResource;
use App\Modules\ERP\Http\Resources\TrialBalanceResource;
use App\Modules\ERP\Services\BalanceSheetService;
use App\Modules\ERP\Services\GeneralLedgerService;
use App\Modules\ERP\Services\ProfitLossService;
use App\Modules\ERP\Services\TrialBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialReportController extends Controller
{
    protected TrialBalanceService $trialBalanceService;
    protected GeneralLedgerService $generalLedgerService;
    protected ProfitLossService $profitLossService;
    protected BalanceSheetService $balanceSheetService;

    public function __construct(
        TrialBalanceService $trialBalanceService,
        GeneralLedgerService $generalLedgerService,
        ProfitLossService $profitLossService,
        BalanceSheetService $balanceSheetService
    ) {
        $this->trialBalanceService = $trialBalanceService;
        $this->generalLedgerService = $generalLedgerService;
        $this->profitLossService = $profitLossService;
        $this->balanceSheetService = $balanceSheetService;
    }

    /**
     * Get Trial Balance report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\Account::class);

        $validated = $request->validate([
            'fiscal_period_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'include_opening_balance' => ['sometimes', 'boolean'],
        ]);

        try {
            $trialBalance = $this->trialBalanceService->generateTrialBalance(
                $validated['fiscal_period_id'],
                $validated['include_opening_balance'] ?? true
            );

            return response()->json([
                'data' => new TrialBalanceResource($trialBalance),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get General Ledger report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generalLedger(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\Account::class);

        $validated = $request->validate([
            'account_id' => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'fiscal_period_id' => [
                'nullable',
                'integer',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        try {
            $generalLedger = $this->generalLedgerService->generateGeneralLedger(
                $validated['account_id'],
                $validated['fiscal_period_id'] ?? null,
                $validated['date_from'] ?? null,
                $validated['date_to'] ?? null
            );

            return response()->json([
                'data' => new GeneralLedgerResource($generalLedger),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Profit & Loss Statement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profitLoss(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\Account::class);

        $validated = $request->validate([
            'fiscal_period_id' => [
                'required',
                'integer',
                Rule::exists('fiscal_periods', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'include_previous_periods' => ['sometimes', 'boolean'],
        ]);

        try {
            $profitLoss = $this->profitLossService->generateProfitLoss(
                $validated['fiscal_period_id'],
                $validated['include_previous_periods'] ?? false
            );

            return response()->json([
                'data' => new ProfitLossResource($profitLoss),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Balance Sheet.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function balanceSheet(Request $request): JsonResponse
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
            $balanceSheet = $this->balanceSheetService->generateBalanceSheet(
                $validated['fiscal_period_id']
            );

            return response()->json([
                'data' => new BalanceSheetResource($balanceSheet),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
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
            $vatReportService = app(\App\Modules\ERP\Services\VatReportService::class);
            $vatReturn = $vatReportService->generateVatReturn(
                $validated['fiscal_period_id']
            );

            return response()->json([
                'data' => new \App\Modules\ERP\Http\Resources\VatReturnResource($vatReturn),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

