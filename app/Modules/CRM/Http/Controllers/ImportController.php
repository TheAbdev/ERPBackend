<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\ImportRequest;
use App\Modules\CRM\Http\Resources\ImportResultResource;
use App\Modules\CRM\Models\ImportResult;
use App\Modules\CRM\Services\Import\ImportService;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Upload and process import file.
     *
     * @param  \App\Modules\CRM\Http\Requests\ImportRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ImportRequest $request): JsonResponse
    {
        $this->authorize('create', ImportResult::class);

        $file = $request->file('file');
        $importType = $request->validated()['import_type'];
        $userId = $request->user()->id;

        $importResult = $this->importService->processImport($file, $importType, $userId);

        return response()->json([
            'message' => 'Import queued successfully.',
            'data' => new ImportResultResource($importResult),
        ], 201);
    }

    /**
     * Get import result by ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $importResult = $this->importService->getImportResult($id);

        if (! $importResult) {
            return response()->json([
                'message' => 'Import result not found.',
            ], 404);
        }

        $this->authorize('view', $importResult);

        return response()->json([
            'data' => new ImportResultResource($importResult),
        ]);
    }

    /**
     * List import results.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ImportResult::class);

        $importResults = ImportResult::where('tenant_id', request()->user()->tenant_id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return response()->json([
            'data' => ImportResultResource::collection($importResults),
            'meta' => [
                'current_page' => $importResults->currentPage(),
                'last_page' => $importResults->lastPage(),
                'per_page' => $importResults->perPage(),
                'total' => $importResults->total(),
            ],
        ]);
    }
}

