<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\ExportRequest;
use App\Modules\CRM\Http\Resources\ExportLogResource;
use App\Modules\CRM\Models\ExportLog;
use App\Modules\CRM\Services\Export\ExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    protected ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Create export.
     *
     * @param  \App\Modules\CRM\Http\Requests\ExportRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ExportRequest $request): JsonResponse
    {
        $this->authorize('create', ExportLog::class);

        $validated = $request->validated();
        $exportType = $validated['export_type'];
        $filters = $validated['filters'] ?? [];
        $format = $validated['format'] ?? 'csv';
        $userId = $request->user()->id;

        $exportLog = $this->exportService->export($exportType, $filters, $userId, $format);

        return response()->json([
            'message' => 'Export created successfully.',
            'data' => new ExportLogResource($exportLog),
        ], 201);
    }

    /**
     * Download export file.
     *
     * @param  int  $exportLog
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(int $exportLog): StreamedResponse
    {
        $exportLogModel = ExportLog::where('tenant_id', request()->user()->tenant_id)
            ->findOrFail($exportLog);

        $this->authorize('view', $exportLogModel);

        // Verify signed URL if provided
        if (request()->has('signature')) {
            if (! $exportLogModel->isUrlValid()) {
                abort(403, 'Download link has expired.');
            }
        }

        if (! Storage::exists($exportLogModel->file_path)) {
            abort(404, 'Export file not found.');
        }

        return Storage::download($exportLogModel->file_path, $exportLogModel->file_name);
    }

    /**
     * List export logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ExportLog::class);

        $exportLogs = ExportLog::where('tenant_id', request()->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return response()->json([
            'data' => ExportLogResource::collection($exportLogs),
            'meta' => [
                'current_page' => $exportLogs->currentPage(),
                'last_page' => $exportLogs->lastPage(),
                'per_page' => $exportLogs->perPage(),
                'total' => $exportLogs->total(),
            ],
        ]);
    }
}

