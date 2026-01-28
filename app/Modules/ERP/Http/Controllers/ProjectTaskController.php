<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\Project;
use App\Modules\ERP\Models\ProjectTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectTaskController extends Controller
{
    /**
     * Display a listing of tasks for a project.
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $query = ProjectTask::with(['assignee', 'creator', 'activity'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('project_id', $project->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        $tasks = $query->latest()->paginate();

        return \App\Modules\ERP\Http\Resources\ProjectTaskResource::collection($tasks);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:todo,in_progress,completed,cancelled',
            'priority' => 'sometimes|integer|in:0,1,2',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'activity_id' => 'nullable|exists:activities,id',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['project_id'] = $project->id;
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'todo';
        $validated['priority'] = $validated['priority'] ?? 0;

        $task = ProjectTask::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project task created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ProjectTaskResource($task->load(['assignee', 'creator'])),
        ], 201);
    }

    /**
     * Display the specified task.
     */
    public function show(Project $project, ProjectTask $projectTask): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('view', $projectTask);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\ProjectTaskResource(
                $projectTask->load(['assignee', 'creator', 'activity', 'project'])
            ),
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Project $project, ProjectTask $projectTask): JsonResponse
    {
        $this->authorize('update', $project);
        $this->authorize('update', $projectTask);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:todo,in_progress,completed,cancelled',
            'priority' => 'sometimes|integer|in:0,1,2',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'activity_id' => 'nullable|exists:activities,id',
        ]);

        $projectTask->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project task updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ProjectTaskResource($projectTask->load(['assignee', 'creator'])),
        ]);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Project $project, ProjectTask $projectTask): JsonResponse
    {
        $this->authorize('update', $project);
        $this->authorize('delete', $projectTask);

        $projectTask->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project task deleted successfully.',
        ]);
    }
}



























