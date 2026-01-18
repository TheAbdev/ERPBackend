<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\StoreActivityRequest;
use App\Modules\CRM\Http\Requests\UpdateActivityRequest;
use App\Modules\CRM\Http\Resources\ActivityResource;
use App\Modules\CRM\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Activity::class);

        $query = Activity::with(['creator', 'assignee', 'related']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned_to
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by due_date (upcoming, overdue, today)
        if ($request->has('due_date_filter')) {
            $now = now();
            match ($request->due_date_filter) {
                'overdue' => $query->where('due_date', '<', $now)->where('status', '!=', 'completed'),
                'today' => $query->whereDate('due_date', $now->toDateString()),
                'upcoming' => $query->where('due_date', '>', $now),
                default => null,
            };
        }

        // Filter by related entity
        if ($request->has('related_type') && $request->has('related_id')) {
            $query->where('related_type', $request->related_type)
                ->where('related_id', $request->related_id);
        }

        $activities = $query->latest('due_date')->paginate();

        return ActivityResource::collection($activities);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StoreActivityRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreActivityRequest $request): JsonResponse
    {
        $this->authorize('create', Activity::class);

        $activity = Activity::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $activity->load(['creator', 'assignee', 'related']);

        return response()->json([
            'data' => new ActivityResource($activity),
            'message' => 'Activity created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        $activity->load(['creator', 'assignee', 'related']);

        return response()->json([
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdateActivityRequest  $request
     * @param  \App\Modules\CRM\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity->update($request->validated());
        $activity->load(['creator', 'assignee', 'related']);

        return response()->json([
            'data' => new ActivityResource($activity),
            'message' => 'Activity updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Activity $activity): JsonResponse
    {
        $this->authorize('delete', $activity);

        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully.',
        ]);
    }

    /**
     * Mark activity as completed.
     *
     * @param  \App\Modules\CRM\Models\Activity  $activity
     * @return \Illuminate\Http\JsonResponse
     */
    public function markCompleted(Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity->update(['status' => 'completed']);
        $activity->load(['creator', 'assignee', 'related']);

        return response()->json([
            'data' => new ActivityResource($activity),
            'message' => 'Activity marked as completed.',
        ]);
    }
}

