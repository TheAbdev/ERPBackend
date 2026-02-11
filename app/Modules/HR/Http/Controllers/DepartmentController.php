<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreDepartmentRequest;
use App\Modules\HR\Http\Requests\UpdateDepartmentRequest;
use App\Modules\HR\Http\Resources\DepartmentResource;
use App\Modules\HR\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Department::class);

        $query = Department::with(['parent', 'manager'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return DepartmentResource::collection($query->latest()->paginate());
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $payload = array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id]
        );

        $department = Department::create($payload);

        event(new EntityCreated($department, $request->user()->id));

        return response()->json([
            'message' => 'Department created successfully.',
            'data' => new DepartmentResource($department->load(['parent', 'manager'])),
        ], 201);
    }

    public function show(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        return response()->json([
            'data' => new DepartmentResource($department->load(['parent', 'manager'])),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $department->update($request->validated());

        event(new EntityUpdated($department->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Department updated successfully.',
            'data' => new DepartmentResource($department->load(['parent', 'manager'])),
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        event(new EntityDeleted($department, request()->user()->id));

        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully.',
        ]);
    }
}
