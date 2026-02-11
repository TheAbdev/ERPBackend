<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreContractRequest;
use App\Modules\HR\Http\Requests\UpdateContractRequest;
use App\Modules\HR\Http\Resources\ContractResource;
use App\Modules\HR\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContractController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Contract::class);

        $query = Contract::with(['employee'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $contracts = $query->latest()->paginate();

        return ContractResource::collection($contracts);
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        $this->authorize('create', Contract::class);

        $contract = Contract::create($request->validated());

        event(new EntityCreated($contract, $request->user()->id));

        return response()->json([
            'message' => 'Contract created successfully.',
            'data' => new ContractResource($contract->load(['employee'])),
        ], 201);
    }

    public function show(Contract $contract): JsonResponse
    {
        $this->authorize('view', $contract);

        return response()->json([
            'data' => new ContractResource($contract->load(['employee'])),
        ]);
    }

    public function update(UpdateContractRequest $request, Contract $contract): JsonResponse
    {
        $this->authorize('update', $contract);

        $contract->update($request->validated());

        event(new EntityUpdated($contract->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Contract updated successfully.',
            'data' => new ContractResource($contract->load(['employee'])),
        ]);
    }

    public function destroy(Contract $contract): JsonResponse
    {
        $this->authorize('delete', $contract);

        event(new EntityDeleted($contract, request()->user()->id));

        $contract->delete();

        return response()->json([
            'message' => 'Contract deleted successfully.',
        ]);
    }
}

