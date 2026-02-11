<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreEmployeeRequest;
use App\Modules\HR\Http\Requests\UpdateEmployeeRequest;
use App\Modules\HR\Http\Resources\EmployeeResource;
use App\Modules\HR\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Employee::class);

        $query = Employee::with(['department', 'position', 'manager'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->has('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return EmployeeResource::collection($query->latest()->paginate());
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $payload = array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id]
        );

        $employee = Employee::create($payload);

        event(new EntityCreated($employee, $request->user()->id));

        return response()->json([
            'message' => 'Employee created successfully.',
            'data' => new EmployeeResource($employee->load(['department', 'position', 'manager'])),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        return response()->json([
            'data' => new EmployeeResource($employee->load(['department', 'position', 'manager'])),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $employee->update($request->validated());

        event(new EntityUpdated($employee->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Employee updated successfully.',
            'data' => new EmployeeResource($employee->load(['department', 'position', 'manager'])),
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        event(new EntityDeleted($employee, request()->user()->id));

        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }
}
