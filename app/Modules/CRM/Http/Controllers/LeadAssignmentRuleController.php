<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Resources\LeadAssignmentRuleResource;
use App\Modules\CRM\Models\LeadAssignmentRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadAssignmentRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', LeadAssignmentRule::class);

        $rules = LeadAssignmentRule::with(['assignedUser', 'assignedTeam'])
            ->latest()
            ->paginate();

        return LeadAssignmentRuleResource::collection($rules);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', LeadAssignmentRule::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|integer|min:0',
            'conditions' => 'required|array',
            'assignment_type' => 'required|string|in:user,team,round_robin',
            'assigned_user_id' => 'required_if:assignment_type,user|exists:users,id',
            'assigned_team_id' => 'required_if:assignment_type,team|exists:teams,id',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        $rule = LeadAssignmentRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lead assignment rule created successfully.',
            'data' => new LeadAssignmentRuleResource($rule),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LeadAssignmentRule $leadAssignmentRule): JsonResponse
    {
        $this->authorize('view', $leadAssignmentRule);

        return response()->json([
            'success' => true,
            'data' => new LeadAssignmentRuleResource($leadAssignmentRule->load(['assignedUser', 'assignedTeam'])),
        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, LeadAssignmentRule $leadAssignmentRule): JsonResponse
    {
        $this->authorize('update', $leadAssignmentRule);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|required|integer|min:0',
            'conditions' => 'sometimes|required|array',
            'assignment_type' => 'sometimes|required|string|in:user,team,round_robin',
            'assigned_user_id' => 'required_if:assignment_type,user|exists:users,id',
            'assigned_team_id' => 'required_if:assignment_type,team|exists:teams,id',
            'is_active' => 'boolean',
        ]);

        $leadAssignmentRule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lead assignment rule updated successfully.',
            'data' => new LeadAssignmentRuleResource($leadAssignmentRule->fresh(['assignedUser', 'assignedTeam'])),
        ]);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(LeadAssignmentRule $leadAssignmentRule): JsonResponse
    {
        $this->authorize('delete', $leadAssignmentRule);

        $leadAssignmentRule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead assignment rule deleted successfully.',
        ]);
    }
}

