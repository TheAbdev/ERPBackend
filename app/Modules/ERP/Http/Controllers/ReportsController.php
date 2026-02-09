<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\ReportFilterRequest;
use App\Modules\ERP\Services\Reports\ErpReportsService;
use Illuminate\Http\JsonResponse;

class ReportsController extends Controller
{
    protected ErpReportsService $erpReportsService;

    public function __construct(ErpReportsService $erpReportsService)
    {
        $this->erpReportsService = $erpReportsService;
    }

    public function products(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->erpReportsService->products($request->validated()),
        ]);
    }

    public function productCategories(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->erpReportsService->productCategories($request->validated()),
        ]);
    }

    public function suppliers(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->erpReportsService->suppliers($request->validated()),
        ]);
    }

    public function purchaseOrders(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->erpReportsService->purchaseOrders($request->validated()),
        ]);
    }

    public function salesOrders(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->erpReportsService->salesOrders($request->validated()),
        ]);
    }

    public function invoices(ReportFilterRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->erpReportsService->invoices($request->validated()),
        ]);
    }
}
