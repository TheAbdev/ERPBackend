<?php

namespace App\Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreEmploymentContractRequest;
use App\Modules\HR\Http\Requests\UpdateEmploymentContractRequest;
use App\Modules\HR\Http\Resources\EmploymentContractResource;
use App\Modules\HR\Models\EmploymentContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmploymentContractController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EmploymentContract::class);

        $query = EmploymentContract::with(['employee', 'currency'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return EmploymentContractResource::collection($query->latest()->paginate());
    }

    public function store(StoreEmploymentContractRequest $request): JsonResponse
    {
        $this->authorize('create', EmploymentContract::class);

        $contract = EmploymentContract::create(array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id]
        ));

        return response()->json([
            'message' => 'Employment contract created successfully.',
            'data' => new EmploymentContractResource($contract->load(['employee', 'currency'])),
        ], 201);
    }

    public function show(EmploymentContract $employmentContract): JsonResponse
    {
        $this->authorize('view', $employmentContract);

        return response()->json([
            'data' => new EmploymentContractResource($employmentContract->load(['employee', 'currency'])),
        ]);
    }

    public function update(UpdateEmploymentContractRequest $request, EmploymentContract $employmentContract): JsonResponse
    {
        $this->authorize('update', $employmentContract);

        $employmentContract->update($request->validated());

        return response()->json([
            'message' => 'Employment contract updated successfully.',
            'data' => new EmploymentContractResource($employmentContract->load(['employee', 'currency'])),
        ]);
    }

    public function destroy(EmploymentContract $employmentContract): JsonResponse
    {
        $this->authorize('delete', $employmentContract);

        $employmentContract->delete();

        return response()->json([
            'message' => 'Employment contract deleted successfully.',
        ]);
    }
}

