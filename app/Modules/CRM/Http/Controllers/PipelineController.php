<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\StorePipelineRequest;
use App\Modules\CRM\Http\Requests\UpdatePipelineRequest;
use App\Modules\CRM\Http\Resources\PipelineResource;
use App\Modules\CRM\Models\Pipeline;
use App\Modules\CRM\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PipelineController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Pipeline::class);

        $pipelines = Pipeline::with('stages')
            ->latest()
            ->paginate();

        return PipelineResource::collection($pipelines);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StorePipelineRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePipelineRequest $request): JsonResponse
    {
        $this->authorize('create', Pipeline::class);

        // If this is set as default, unset other defaults
        if ($request->is_default) {
            Pipeline::where('tenant_id', $request->user()->tenant_id)
                ->update(['is_default' => false]);
        }

        $pipeline = Pipeline::create($request->validated());
        $pipeline->load('stages');

        return response()->json([
            'data' => new PipelineResource($pipeline),
            'message' => 'Pipeline created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Pipeline  $pipeline
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Pipeline $pipeline): JsonResponse
    {
        $this->authorize('view', $pipeline);

        $pipeline->load('stages');

        return response()->json([
            'data' => new PipelineResource($pipeline),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdatePipelineRequest  $request
     * @param  \App\Modules\CRM\Models\Pipeline  $pipeline
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePipelineRequest $request, Pipeline $pipeline): JsonResponse
    {
        $this->authorize('update', $pipeline);

        // If this is set as default, unset other defaults
        if ($request->has('is_default') && $request->is_default) {
            Pipeline::where('tenant_id', $request->user()->tenant_id)
                ->where('id', '!=', $pipeline->id)
                ->update(['is_default' => false]);
        }

        $pipeline->update($request->validated());
        $pipeline->load('stages');

        return response()->json([
            'data' => new PipelineResource($pipeline),
            'message' => 'Pipeline updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Pipeline  $pipeline
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Pipeline $pipeline): JsonResponse
    {
        $this->authorize('delete', $pipeline);

        $pipeline->delete();

        return response()->json([
            'message' => 'Pipeline deleted successfully.',
        ]);
    }

    /**
     * Create a stage for the pipeline.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\CRM\Models\Pipeline  $pipeline
     * @return \Illuminate\Http\JsonResponse
     */
    public function createStage(Request $request, Pipeline $pipeline): JsonResponse
    {
        $this->authorize('update', $pipeline);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $stage = $pipeline->stages()->create([
            'name' => $request->name,
            'position' => $request->position ?? $pipeline->stages()->max('position') + 1,
            'probability' => $request->probability ?? 0,
        ]);

        return response()->json([
            'data' => new \App\Modules\CRM\Http\Resources\PipelineStageResource($stage),
            'message' => 'Stage created successfully.',
        ], 201);
    }

    /**
     * Update a stage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\CRM\Models\Pipeline  $pipeline
     * @param  \App\Modules\CRM\Models\PipelineStage  $stage
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStage(Request $request, Pipeline $pipeline, PipelineStage $stage): JsonResponse
    {
        $this->authorize('update', $pipeline);

        $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $stage->update($request->only(['name', 'position', 'probability']));

        return response()->json([
            'data' => new \App\Modules\CRM\Http\Resources\PipelineStageResource($stage),
            'message' => 'Stage updated successfully.',
        ]);
    }

    /**
     * Delete a stage.
     *
     * @param  \App\Modules\CRM\Models\Pipeline  $pipeline
     * @param  \App\Modules\CRM\Models\PipelineStage  $stage
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyStage(Pipeline $pipeline, PipelineStage $stage): JsonResponse
    {
        $this->authorize('update', $pipeline);

        // Verify stage belongs to pipeline
        if ($stage->pipeline_id !== $pipeline->id) {
            return response()->json([
                'message' => 'Stage does not belong to this pipeline.',
            ], 403);
        }

        // Check if stage has deals
        $dealsCount = \App\Modules\CRM\Models\Deal::where('stage_id', $stage->id)->count();
        if ($dealsCount > 0) {
            return response()->json([
                'message' => "Cannot delete stage. It has {$dealsCount} deal(s) associated with it.",
            ], 422);
        }

        $stage->delete();

        return response()->json([
            'message' => 'Stage deleted successfully.',
        ]);
    }

    /**
     * Reorder stages.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\CRM\Models\Pipeline  $pipeline
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderStages(Request $request, Pipeline $pipeline): JsonResponse
    {
        $this->authorize('update', $pipeline);

        $request->validate([
            'stage_ids' => ['required', 'array'],
            'stage_ids.*' => ['exists:pipeline_stages,id'],
        ]);

        foreach ($request->stage_ids as $position => $stageId) {
            PipelineStage::where('id', $stageId)
                ->where('pipeline_id', $pipeline->id)
                ->update(['position' => $position]);
        }

        $pipeline->load('stages');

        return response()->json([
            'data' => new PipelineResource($pipeline),
            'message' => 'Stages reordered successfully.',
        ]);
    }
}

