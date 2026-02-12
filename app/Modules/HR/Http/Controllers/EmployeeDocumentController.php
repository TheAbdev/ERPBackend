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
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $payload = $request->validated();

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('hr/employee-documents', 'public');
            $payload['file_path'] = '/storage/' . $path;
        }

        $document = EmployeeDocument::create($payload);

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

    public function download(EmployeeDocument $employeeDocument): StreamedResponse
    {
        $this->authorize('view', $employeeDocument);

        if ((int) request()->user()->tenant_id !== (int) $employeeDocument->tenant_id) {
            abort(403);
        }

        $path = $employeeDocument->file_path;
        if (! $path) {
            abort(404, 'File not found.');
        }

        $publicRelative = ltrim(str_replace('/storage/', '', $path), '/');
        if ($publicRelative === '' || ! Storage::disk('public')->exists($publicRelative)) {
            abort(404, 'File not found.');
        }

        $ext = pathinfo($publicRelative, PATHINFO_EXTENSION);
        $safeName = trim((string) $employeeDocument->name);
        $downloadName = $safeName !== '' ? $safeName : ('employee-document-' . $employeeDocument->id);
        if ($ext) {
            $downloadName .= '.' . $ext;
        }

        return Storage::disk('public')->download($publicRelative, $downloadName);
    }

    public function update(UpdateEmployeeDocumentRequest $request, EmployeeDocument $employeeDocument): JsonResponse
    {
        $this->authorize('update', $employeeDocument);

        $payload = $request->validated();

        if ($request->hasFile('file')) {
            $oldPath = $employeeDocument->file_path;
            if ($oldPath) {
                $publicRelative = ltrim(str_replace('/storage/', '', $oldPath), '/');
                if ($publicRelative !== '' && Storage::disk('public')->exists($publicRelative)) {
                    Storage::disk('public')->delete($publicRelative);
                }
            }

            $path = $request->file('file')->store('hr/employee-documents', 'public');
            $payload['file_path'] = '/storage/' . $path;
        }

        $employeeDocument->update($payload);

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

        $oldPath = $employeeDocument->file_path;
        if ($oldPath) {
            $publicRelative = ltrim(str_replace('/storage/', '', $oldPath), '/');
            if ($publicRelative !== '' && Storage::disk('public')->exists($publicRelative)) {
                Storage::disk('public')->delete($publicRelative);
            }
        }

        $employeeDocument->delete();

        return response()->json([
            'message' => 'Employee document deleted successfully.',
        ]);
    }
}

