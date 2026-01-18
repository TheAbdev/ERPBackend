<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Services\SupplierReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierReportController extends Controller
{
    protected SupplierReportService $supplierReportService;

    public function __construct(SupplierReportService $supplierReportService)
    {
        $this->supplierReportService = $supplierReportService;
    }

    public function performance(Request $request, int $supplierId): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\PurchaseOrder::class);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $report = $this->supplierReportService->getSupplierPerformance($supplierId, $dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\PurchaseOrder::class);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $summary = $this->supplierReportService->getSupplierSummary($dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}

