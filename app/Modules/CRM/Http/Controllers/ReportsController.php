<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\ReportFilterRequest;
use App\Modules\CRM\Services\Reports\ActivitiesReportService;
use App\Modules\CRM\Services\Reports\DealsReportService;
use App\Modules\CRM\Services\Reports\LeadsReportService;
use App\Modules\CRM\Services\Reports\SalesPerformanceService;
use Illuminate\Http\JsonResponse;

class ReportsController extends Controller
{
    protected LeadsReportService $leadsReportService;
    protected DealsReportService $dealsReportService;
    protected ActivitiesReportService $activitiesReportService;
    protected SalesPerformanceService $salesPerformanceService;

    public function __construct(
        LeadsReportService $leadsReportService,
        DealsReportService $dealsReportService,
        ActivitiesReportService $activitiesReportService,
        SalesPerformanceService $salesPerformanceService
    ) {
        $this->leadsReportService = $leadsReportService;
        $this->dealsReportService = $dealsReportService;
        $this->activitiesReportService = $activitiesReportService;
        $this->salesPerformanceService = $salesPerformanceService;
    }

    /**
     * Get leads report.
     *
     * @param  \App\Modules\CRM\Http\Requests\ReportFilterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function leads(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return response()->json([
            'data' => [
                'total_leads' => $this->leadsReportService->getTotalLeads($filters),
                'by_source' => $this->leadsReportService->getLeadsBySource($filters),
                'by_status' => $this->leadsReportService->getLeadsByStatus($filters),
                'conversion_rate' => $this->leadsReportService->getConversionRate($filters),
            ],
        ]);
    }

    /**
     * Get deals report.
     *
     * @param  \App\Modules\CRM\Http\Requests\ReportFilterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deals(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $dealValue = $this->dealsReportService->getTotalDealValue($filters);

        return response()->json([
            'data' => [
                'total_deals' => $this->dealsReportService->getTotalDeals($filters),
                'total_value' => $dealValue['total_value'] ?? 0,
                'by_stage' => $this->dealsReportService->getByStage($filters),
                'by_status' => $this->dealsReportService->getByStatus($filters),
                'win_rate' => $this->dealsReportService->getWinRate($filters),
            ],
        ]);
    }

    /**
     * Get activities report.
     *
     * @param  \App\Modules\CRM\Http\Requests\ReportFilterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activities(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return response()->json([
            'data' => [
                'total_activities' => $this->activitiesReportService->getTotalActivities($filters),
                'by_type' => $this->activitiesReportService->getByType($filters),
                'by_status' => $this->activitiesReportService->getByStatus($filters),
                'completion_rate' => $this->activitiesReportService->getCompletionRate($filters),
            ],
        ]);
    }

    /**
     * Get sales performance report.
     *
     * @param  \App\Modules\CRM\Http\Requests\ReportFilterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function salesPerformance(ReportFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return response()->json([
            'data' => [
                'total_revenue' => $this->salesPerformanceService->getTotalRevenue($filters),
                'by_period' => $this->salesPerformanceService->getByPeriod($filters),
                'by_user' => $this->salesPerformanceService->getByUser($filters),
                'average_deal_size' => $this->salesPerformanceService->getAverageDealSize($filters),
            ],
        ]);
    }
}

