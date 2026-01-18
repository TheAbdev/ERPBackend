<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\WorkflowResource;
use App\Modules\ERP\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    /**
     * Display a listing of workflows.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Workflow::class);

        $query = Workflow::with('steps.role')
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $workflows = $query->orderBy('name')->paginate();

        return WorkflowResource::collection($workflows);
    }

    /**
     * Store a newly created workflow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $validated = $request->validate([
            'entity_type' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['required', 'integer', 'min:1'],
            'steps.*.role_id' => [
                'nullable',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($query) => $query->where('tenant_id', $request->user()->tenant_id)),
            ],
            'steps.*.permission' => ['nullable', 'string'],
            'steps.*.action' => ['sometimes', 'string', Rule::in(['approve', 'reject'])],
            'steps.*.auto_approve' => ['sometimes', 'boolean'],
            'steps.*.description' => ['nullable', 'string'],
        ]);

        $workflow = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request) {
            $workflow = Workflow::create([
                'tenant_id' => $request->user()->tenant_id,
                'entity_type' => $validated['entity_type'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['steps'] as $stepData) {
                $workflow->steps()->create([
                    'tenant_id' => $request->user()->tenant_id,
                    'workflow_id' => $workflow->id,
                    'step_order' => $stepData['step_order'],
                    'role_id' => $stepData['role_id'] ?? null,
                    'permission' => $stepData['permission'] ?? null,
                    'action' => $stepData['action'] ?? 'approve',
                    'auto_approve' => $stepData['auto_approve'] ?? false,
                    'description' => $stepData['description'] ?? null,
                ]);
            }

            return $workflow->load('steps.role');
        });

        return response()->json([
            'message' => 'Workflow created successfully.',
            'data' => new WorkflowResource($workflow),
        ], 201);
    }

    /**
     * Display the specified workflow.
     *
     * @param  \App\Modules\ERP\Models\Workflow  $workflow
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        $workflow->load('steps.role');

        return response()->json([
            'data' => new WorkflowResource($workflow),
        ]);
    }

    /**
     * Update the specified workflow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Workflow  $workflow
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $workflow->update($validated);

        return response()->json([
            'message' => 'Workflow updated successfully.',
            'data' => new WorkflowResource($workflow->fresh()->load('steps.role')),
        ]);
    }

    /**
     * Remove the specified workflow.
     *
     * @param  \App\Modules\ERP\Models\Workflow  $workflow
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Workflow $workflow): JsonResponse
    {
        $this->authorize('delete', $workflow);

        // Check if workflow has active instances
        if ($workflow->instances()->where('status', 'pending')->exists()) {
            return response()->json([
                'message' => 'Cannot delete workflow with pending instances.',
            ], 422);
        }

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully.',
        ]);
    }
}

