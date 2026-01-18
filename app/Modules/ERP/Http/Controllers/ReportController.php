<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\ReportResource;
use App\Modules\ERP\Models\Report;
use App\Modules\ERP\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Report::class);

        $query = Report::where('tenant_id', $request->user()->tenant_id)
            ->with('creator');

        if ($request->has('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $reports = $query->orderBy('name')->paginate();

        return ReportResource::collection($reports);
    }

    /**
     * Display the specified report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Report  $report
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        $filters = $request->input('filters');
        $data = $this->reportService->generateReport($report->id, $filters);

        return response()->json([
            'report' => new ReportResource($report->load('creator')),
            'data' => $data,
        ]);
    }

    /**
     * Export report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Report  $report
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request, Report $report)
    {
        $this->authorize('view', $report);

        $validated = $request->validate([
            'format' => ['sometimes', 'string', 'in:csv,excel,pdf,json'],
            'filters' => ['nullable', 'array'],
        ]);

        $format = $validated['format'] ?? 'json';
        $filters = $validated['filters'] ?? null;

        $data = $this->reportService->exportReport($report->id, $format, $filters);

        if ($format === 'json') {
            return response()->json(['data' => json_decode($data, true)]);
        }

        // For CSV/Excel/PDF, would need proper export library
        return response()->json([
            'message' => 'Export format not yet implemented. Use JSON format.',
            'data' => json_decode($data, true),
        ]);
    }
}

