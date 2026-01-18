<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\Timesheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimesheetController extends Controller
{
    /**
     * Display a listing of timesheets.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Timesheet::class);

        $query = Timesheet::with(['user', 'project', 'projectTask', 'approver'])
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by user (if not admin, only show own timesheets)
        if (!$request->user()->hasRole('admin') && !$request->user()->hasRole('super_admin')) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $timesheets = $query->latest('date')->paginate();

        return \App\Modules\ERP\Http\Resources\TimesheetResource::collection($timesheets);
    }

    /**
     * Store a newly created timesheet.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Timesheet::class);

        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'project_task_id' => 'nullable|exists:project_tasks,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.01|max:24',
            'description' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'draft';

        // Check for duplicate entry
        $existing = Timesheet::where('tenant_id', $validated['tenant_id'])
            ->where('user_id', $validated['user_id'])
            ->where('project_id', $validated['project_id'])
            ->where('project_task_id', $validated['project_task_id'])
            ->where('date', $validated['date'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Timesheet entry already exists for this date and project/task.',
            ], 422);
        }

        $timesheet = Timesheet::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\TimesheetResource($timesheet->load(['user', 'project', 'projectTask'])),
        ], 201);
    }

    /**
     * Display the specified timesheet.
     */
    public function show(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('view', $timesheet);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\TimesheetResource(
                $timesheet->load(['user', 'project', 'projectTask', 'approver'])
            ),
        ]);
    }

    /**
     * Update the specified timesheet.
     */
    public function update(Request $request, Timesheet $timesheet): JsonResponse
    {
        $this->authorize('update', $timesheet);

        // Can only update draft timesheets
        if ($timesheet->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft timesheets can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'project_task_id' => 'nullable|exists:project_tasks,id',
            'date' => 'sometimes|required|date',
            'hours' => 'sometimes|required|numeric|min:0.01|max:24',
            'description' => 'nullable|string',
        ]);

        $timesheet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\TimesheetResource($timesheet->load(['user', 'project', 'projectTask'])),
        ]);
    }

    /**
     * Remove the specified timesheet.
     */
    public function destroy(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('delete', $timesheet);

        // Can only delete draft timesheets
        if ($timesheet->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft timesheets can be deleted.',
            ], 422);
        }

        $timesheet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Timesheet deleted successfully.',
        ]);
    }

    /**
     * Submit timesheet for approval.
     */
    public function submit(Request $request, Timesheet $timesheet): JsonResponse
    {
        $this->authorize('update', $timesheet);

        if (!$timesheet->canBeSubmitted()) {
            return response()->json([
                'success' => false,
                'message' => 'Timesheet cannot be submitted. Only draft timesheets can be submitted.',
            ], 422);
        }

        $timesheet->update(['status' => 'submitted']);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet submitted for approval.',
            'data' => new \App\Modules\ERP\Http\Resources\TimesheetResource($timesheet->load(['user', 'project', 'projectTask'])),
        ]);
    }

    /**
     * Approve timesheet.
     */
    public function approve(Request $request, Timesheet $timesheet): JsonResponse
    {
        $this->authorize('approve', $timesheet);

        if (!$timesheet->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Timesheet cannot be approved. Only submitted timesheets can be approved.',
            ], 422);
        }

        $timesheet->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // Update project task actual hours if linked
        if ($timesheet->project_task_id) {
            $task = $timesheet->projectTask;
            $task->increment('actual_hours', $timesheet->hours);
        }

        // Update project actual cost if needed
        if ($timesheet->project_id) {
            // This would require hourly rate calculation
            // For now, just log it
        }

        return response()->json([
            'success' => true,
            'message' => 'Timesheet approved successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\TimesheetResource($timesheet->load(['user', 'project', 'projectTask', 'approver'])),
        ]);
    }

    /**
     * Reject timesheet.
     */
    public function reject(Request $request, Timesheet $timesheet): JsonResponse
    {
        $this->authorize('approve', $timesheet);

        if (!$timesheet->canBeRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Timesheet cannot be rejected. Only submitted timesheets can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $timesheet->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet rejected.',
            'data' => new \App\Modules\ERP\Http\Resources\TimesheetResource($timesheet->load(['user', 'project', 'projectTask'])),
        ]);
    }
}





