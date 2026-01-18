<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\StoreWorkflowRequest;
use App\Modules\CRM\Http\Requests\UpdateWorkflowRequest;
use App\Modules\CRM\Http\Resources\WorkflowResource;
use App\Modules\CRM\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkflowController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Workflow::class);

        $workflows = Workflow::with(['creator'])
            ->latest()
            ->paginate();

        return WorkflowResource::collection($workflows);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StoreWorkflowRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $workflow = Workflow::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $workflow->load(['creator']);

        return response()->json([
            'data' => new WorkflowResource($workflow),
            'message' => 'Workflow created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Workflow  $workflow
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        $workflow->load(['creator', 'runs']);

        return response()->json([
            'data' => new WorkflowResource($workflow),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdateWorkflowRequest  $request
     * @param  \App\Modules\CRM\Models\Workflow  $workflow
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateWorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $workflow->update($request->validated());
        $workflow->load(['creator']);

        return response()->json([
            'data' => new WorkflowResource($workflow),
            'message' => 'Workflow updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Workflow  $workflow
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Workflow $workflow): JsonResponse
    {
        $this->authorize('delete', $workflow);

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully.',
        ]);
    }
}

