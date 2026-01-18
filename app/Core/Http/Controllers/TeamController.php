<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\Team;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Team::class);

        $query = Team::with(['teamLead', 'users'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $teams = $query->latest()->paginate();

        return \App\Core\Http\Resources\TeamResource::collection($teams);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Team::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'team_lead_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = $request->input('is_active', true);

        $team = Team::create($validated);

        // Attach users if provided
        if ($request->has('user_ids')) {
            $team->users()->attach($request->input('user_ids'), [
                'tenant_id' => $request->user()->tenant_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Team created successfully.',
            'data' => new \App\Core\Http\Resources\TeamResource($team->load(['teamLead', 'users'])),
        ], 201);
    }

    public function show(Team $team): JsonResponse
    {
        $this->authorize('view', $team);

        $team->load(['teamLead', 'users']);

        return response()->json([
            'success' => true,
            'data' => new \App\Core\Http\Resources\TeamResource($team),
        ]);
    }

    public function update(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'team_lead_id' => 'nullable|exists:users,id',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $team->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Team updated successfully.',
            'data' => new \App\Core\Http\Resources\TeamResource($team->fresh()->load(['teamLead', 'users'])),
        ]);
    }

    public function destroy(Team $team): JsonResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully.',
        ]);
    }

    public function attachUsers(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'roles' => 'nullable|array',
            'roles.*' => 'string|max:255',
        ]);

        $userIds = $request->input('user_ids');
        $roles = $request->input('roles', []);

        foreach ($userIds as $index => $userId) {
            $team->users()->syncWithoutDetaching([
                $userId => [
                    'tenant_id' => $request->user()->tenant_id,
                    'role' => $roles[$index] ?? 'member',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Users attached to team successfully.',
            'data' => new \App\Core\Http\Resources\TeamResource($team->fresh()->load('users')),
        ]);
    }

    public function detachUsers(Request $request, Team $team): JsonResponse
    {
        $this->authorize('update', $team);

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $team->users()->detach($request->input('user_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Users detached from team successfully.',
            'data' => new \App\Core\Http\Resources\TeamResource($team->fresh()->load('users')),
        ]);
    }
}

