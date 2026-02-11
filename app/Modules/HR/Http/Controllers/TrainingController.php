<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreTrainingRequest;
use App\Modules\HR\Http\Requests\UpdateTrainingRequest;
use App\Modules\HR\Http\Resources\TrainingResource;
use App\Modules\HR\Models\Training;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Training::class);

        $query = Training::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('provider', 'like', "%{$search}%");
        }

        $trainings = $query->latest()->paginate();

        return TrainingResource::collection($trainings);
    }

    public function store(StoreTrainingRequest $request): JsonResponse
    {
        $this->authorize('create', Training::class);

        $training = Training::create($request->validated());

        event(new EntityCreated($training, $request->user()->id));

        return response()->json([
            'message' => 'Training created successfully.',
            'data' => new TrainingResource($training),
        ], 201);
    }

    public function show(Training $training): JsonResponse
    {
        $this->authorize('view', $training);

        return response()->json([
            'data' => new TrainingResource($training),
        ]);
    }

    public function update(UpdateTrainingRequest $request, Training $training): JsonResponse
    {
        $this->authorize('update', $training);

        $training->update($request->validated());

        event(new EntityUpdated($training->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Training updated successfully.',
            'data' => new TrainingResource($training),
        ]);
    }

    public function destroy(Training $training): JsonResponse
    {
        $this->authorize('delete', $training);

        event(new EntityDeleted($training, request()->user()->id));

        $training->delete();

        return response()->json([
            'message' => 'Training deleted successfully.',
        ]);
    }
}

