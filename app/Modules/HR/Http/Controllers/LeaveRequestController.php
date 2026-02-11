<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreLeaveRequest;
use App\Modules\HR\Http\Requests\UpdateLeaveRequest;
use App\Modules\HR\Http\Resources\LeaveRequestResource;
use App\Modules\HR\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $query = LeaveRequest::with(['employee', 'approver'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->latest()->paginate();

        return LeaveRequestResource::collection($requests);
    }

    public function store(StoreLeaveRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveRequest::class);

        $leaveRequest = LeaveRequest::create($request->validated());

        event(new EntityCreated($leaveRequest, $request->user()->id));

        return response()->json([
            'message' => 'Leave request created successfully.',
            'data' => new LeaveRequestResource($leaveRequest->load(['employee', 'approver'])),
        ], 201);
    }

    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('view', $leaveRequest);

        return response()->json([
            'data' => new LeaveRequestResource($leaveRequest->load(['employee', 'approver'])),
        ]);
    }

    public function update(UpdateLeaveRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('update', $leaveRequest);

        $leaveRequest->update($request->validated());

        event(new EntityUpdated($leaveRequest->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Leave request updated successfully.',
            'data' => new LeaveRequestResource($leaveRequest->load(['employee', 'approver'])),
        ]);
    }

    public function destroy(LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorize('delete', $leaveRequest);

        event(new EntityDeleted($leaveRequest, request()->user()->id));

        $leaveRequest->delete();

        return response()->json([
            'message' => 'Leave request deleted successfully.',
        ]);
    }
}

