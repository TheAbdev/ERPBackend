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

        return response()->json([
            'data' => [
                'total_deal_value' => $this->dealsReportService->getTotalDealValue($filters),
                'won_vs_lost' => $this->dealsReportService->getWonVsLost($filters),
                'pipeline_funnel' => $this->dealsReportService->getPipelineFunnel($filters),
                'average_deal_duration' => $this->dealsReportService->getAverageDealDuration($filters),
                'revenue_forecast' => $this->dealsReportService->getRevenueForecast($filters),
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
                'per_user' => $this->activitiesReportService->getActivitiesPerUser($filters),
                'completed_vs_pending' => $this->activitiesReportService->getCompletedVsPending($filters),
                'overdue' => $this->activitiesReportService->getOverdueActivities($filters),
                'type_distribution' => $this->activitiesReportService->getActivityTypeDistribution($filters),
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
                'revenue_per_user' => $this->salesPerformanceService->getRevenuePerUser($filters),
                'deals_closed_per_user' => $this->salesPerformanceService->getDealsClosedPerUser($filters),
                'average_deal_size_per_user' => $this->salesPerformanceService->getAverageDealSizePerUser($filters),
                'win_rate_per_user' => $this->salesPerformanceService->getWinRatePerUser($filters),
            ],
        ]);
    }
}

