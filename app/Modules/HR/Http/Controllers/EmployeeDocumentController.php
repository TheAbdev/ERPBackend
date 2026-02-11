<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreEmployeeDocumentRequest;
use App\Modules\HR\Http\Requests\UpdateEmployeeDocumentRequest;
use App\Modules\HR\Http\Resources\EmployeeDocumentResource;
use App\Modules\HR\Models\EmployeeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeDocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $query = EmployeeDocument::with(['employee'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $documents = $query->latest()->paginate();

        return EmployeeDocumentResource::collection($documents);
    }

    public function store(StoreEmployeeDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', EmployeeDocument::class);

        $document = EmployeeDocument::create($request->validated());

        event(new EntityCreated($document, $request->user()->id));

        return response()->json([
            'message' => 'Employee document created successfully.',
            'data' => new EmployeeDocumentResource($document->load(['employee'])),
        ], 201);
    }

    public function show(EmployeeDocument $employeeDocument): JsonResponse
    {
        $this->authorize('view', $employeeDocument);

        return response()->json([
            'data' => new EmployeeDocumentResource($employeeDocument->load(['employee'])),
        ]);
    }

    public function update(UpdateEmployeeDocumentRequest $request, EmployeeDocument $employeeDocument): JsonResponse
    {
        $this->authorize('update', $employeeDocument);

        $employeeDocument->update($request->validated());

        event(new EntityUpdated($employeeDocument->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Employee document updated successfully.',
            'data' => new EmployeeDocumentResource($employeeDocument->load(['employee'])),
        ]);
    }

    public function destroy(EmployeeDocument $employeeDocument): JsonResponse
    {
        $this->authorize('delete', $employeeDocument);

        event(new EntityDeleted($employeeDocument, request()->user()->id));

        $employeeDocument->delete();

        return response()->json([
            'message' => 'Employee document deleted successfully.',
        ]);
    }
}

