<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\StoreLeadRequest;
use App\Modules\CRM\Http\Requests\UpdateLeadRequest;
use App\Modules\CRM\Http\Resources\LeadResource;
use App\Modules\CRM\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @OA\Tag(
 *     name="CRM - Leads",
 *     description="Lead management endpoints"
 * )
 */
class LeadController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/crm/leads",
     *     summary="List all leads",
     *     tags={"CRM - Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Lead")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     *
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Lead::class);

        $query = Lead::with(['creator', 'assignee']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by source
        if ($request->has('source') && $request->source) {
            $query->where('source', $request->source);
        }

        $leads = $query->latest()->paginate();

        return LeadResource::collection($leads);
    }

    /**
     * @OA\Post(
     *     path="/api/crm/leads",
     *     summary="Create a new lead",
     *     tags={"CRM - Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LeadRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lead created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Lead"),
     *             @OA\Property(property="message", type="string", example="Lead created successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     *
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StoreLeadRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $this->authorize('create', Lead::class);

        $lead = Lead::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $lead->load(['creator', 'assignee']);

        // Dispatch entity created event
        event(new EntityCreated($lead, $request->user()->id));

        return response()->json([
            'data' => new LeadResource($lead),
            'message' => 'Lead created successfully.',
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/crm/leads/{id}",
     *     summary="Get a specific lead",
     *     tags={"CRM - Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lead ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Lead")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lead not found")
     * )
     *
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Lead  $lead
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $lead->load(['creator', 'assignee']);

        return response()->json([
            'data' => new LeadResource($lead),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/crm/leads/{id}",
     *     summary="Update a lead",
     *     tags={"CRM - Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lead ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LeadRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Lead"),
     *             @OA\Property(property="message", type="string", example="Lead updated successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lead not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     *
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdateLeadRequest  $request
     * @param  \App\Modules\CRM\Models\Lead  $lead
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $lead->update($request->validated());
        $lead->load(['creator', 'assignee']);

        // Dispatch entity updated event
        event(new EntityUpdated($lead, $request->user()->id));

        return response()->json([
            'data' => new LeadResource($lead),
            'message' => 'Lead updated successfully.',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/crm/leads/{id}",
     *     summary="Delete a lead",
     *     tags={"CRM - Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lead ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lead deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lead not found")
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Lead  $lead
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $this->authorize('delete', $lead);

        // Dispatch entity deleted event before deletion
        event(new EntityDeleted($lead, request()->user()->id));

        $lead->delete();

        return response()->json([
            'message' => 'Lead deleted successfully.',
        ]);
    }
}

