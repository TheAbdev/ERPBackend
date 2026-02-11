<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreTrainingAssignmentRequest;
use App\Modules\HR\Http\Requests\UpdateTrainingAssignmentRequest;
use App\Modules\HR\Http\Resources\TrainingAssignmentResource;
use App\Modules\HR\Models\TrainingAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainingAssignmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TrainingAssignment::class);

        $query = TrainingAssignment::with(['training', 'employee'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('training_id')) {
            $query->where('training_id', $request->input('training_id'));
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $assignments = $query->latest()->paginate();

        return TrainingAssignmentResource::collection($assignments);
    }

    public function store(StoreTrainingAssignmentRequest $request): JsonResponse
    {
        $this->authorize('create', TrainingAssignment::class);

        $assignment = TrainingAssignment::create($request->validated());

        event(new EntityCreated($assignment, $request->user()->id));

        return response()->json([
            'message' => 'Training assignment created successfully.',
            'data' => new TrainingAssignmentResource($assignment->load(['training', 'employee'])),
        ], 201);
    }

    public function show(TrainingAssignment $trainingAssignment): JsonResponse
    {
        $this->authorize('view', $trainingAssignment);

        return response()->json([
            'data' => new TrainingAssignmentResource($trainingAssignment->load(['training', 'employee'])),
        ]);
    }

    public function update(UpdateTrainingAssignmentRequest $request, TrainingAssignment $trainingAssignment): JsonResponse
    {
        $this->authorize('update', $trainingAssignment);

        $trainingAssignment->update($request->validated());

        event(new EntityUpdated($trainingAssignment->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Training assignment updated successfully.',
            'data' => new TrainingAssignmentResource($trainingAssignment->load(['training', 'employee'])),
        ]);
    }

    public function destroy(TrainingAssignment $trainingAssignment): JsonResponse
    {
        $this->authorize('delete', $trainingAssignment);

        event(new EntityDeleted($trainingAssignment, request()->user()->id));

        $trainingAssignment->delete();

        return response()->json([
            'message' => 'Training assignment deleted successfully.',
        ]);
    }
}

