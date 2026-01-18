<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\WorkflowInstanceResource;
use App\Modules\ERP\Models\WorkflowInstance;
use App\Modules\ERP\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkflowInstanceController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Display a listing of workflow instances.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowInstance::class);

        $query = WorkflowInstance::with(['workflow', 'entity', 'initiator', 'actions.user'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        $instances = $query->orderBy('created_at', 'desc')->paginate();

        return WorkflowInstanceResource::collection($instances);
    }

    /**
     * Display the specified workflow instance.
     *
     * @param  \App\Modules\ERP\Models\WorkflowInstance  $workflowInstance
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(WorkflowInstance $workflowInstance): JsonResponse
    {
        $this->authorize('view', $workflowInstance);

        $workflowInstance->load(['workflow.steps.role', 'entity', 'initiator', 'actions.user']);

        return response()->json([
            'data' => new WorkflowInstanceResource($workflowInstance),
        ]);
    }

    /**
     * Approve a workflow step.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\WorkflowInstance  $workflowInstance
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, WorkflowInstance $workflowInstance): JsonResponse
    {
        $this->authorize('update', $workflowInstance);

        $validated = $request->validate([
            'comment' => ['nullable', 'string'],
        ]);

        try {
            $currentStep = $workflowInstance->getCurrentStep();

            if (!$currentStep) {
                return response()->json([
                    'message' => 'No current step found.',
                ], 422);
            }

            $instance = $this->workflowService->approveStep(
                $workflowInstance,
                $currentStep,
                $request->user()->id,
                $validated['comment'] ?? null
            );

            return response()->json([
                'message' => 'Workflow step approved successfully.',
                'data' => new WorkflowInstanceResource($instance->load(['workflow.steps.role', 'entity', 'actions.user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject a workflow step.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\WorkflowInstance  $workflowInstance
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, WorkflowInstance $workflowInstance): JsonResponse
    {
        $this->authorize('update', $workflowInstance);

        $validated = $request->validate([
            'comment' => ['nullable', 'string'],
        ]);

        try {
            $currentStep = $workflowInstance->getCurrentStep();

            if (!$currentStep) {
                return response()->json([
                    'message' => 'No current step found.',
                ], 422);
            }

            $instance = $this->workflowService->rejectStep(
                $workflowInstance,
                $currentStep,
                $request->user()->id,
                $validated['comment'] ?? null
            );

            return response()->json([
                'message' => 'Workflow step rejected successfully.',
                'data' => new WorkflowInstanceResource($instance->load(['workflow.steps.role', 'entity', 'actions.user'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

