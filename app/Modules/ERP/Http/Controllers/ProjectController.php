<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::with(['manager', 'creator'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->input('manager_id'));
        }

        $projects = $query->latest()->paginate();

        return \App\Modules\ERP\Http\Resources\ProjectResource::collection($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:planning,active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'planning';

        $project = Project::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ProjectResource($project->load(['manager', 'creator'])),
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\ProjectResource(
                $project->load(['manager', 'creator', 'tasks.assignee', 'budgets'])
            ),
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:planning,active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ProjectResource($project->load(['manager', 'creator'])),
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
        ]);
    }
}






