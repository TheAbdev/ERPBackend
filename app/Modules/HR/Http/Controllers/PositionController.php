<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StorePositionRequest;
use App\Modules\HR\Http\Requests\UpdatePositionRequest;
use App\Modules\HR\Http\Resources\PositionResource;
use App\Modules\HR\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PositionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Position::class);

        $query = Position::with(['department'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return PositionResource::collection($query->latest()->paginate());
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        $payload = array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id]
        );

        $position = Position::create($payload);

        event(new EntityCreated($position, $request->user()->id));

        return response()->json([
            'message' => 'Position created successfully.',
            'data' => new PositionResource($position->load(['department'])),
        ], 201);
    }

    public function show(Position $position): JsonResponse
    {
        $this->authorize('view', $position);

        return response()->json([
            'data' => new PositionResource($position->load(['department'])),
        ]);
    }

    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $this->authorize('update', $position);

        $position->update($request->validated());

        event(new EntityUpdated($position->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Position updated successfully.',
            'data' => new PositionResource($position->load(['department'])),
        ]);
    }

    public function destroy(Position $position): JsonResponse
    {
        $this->authorize('delete', $position);

        event(new EntityDeleted($position, request()->user()->id));

        $position->delete();

        return response()->json([
            'message' => 'Position deleted successfully.',
        ]);
    }
}
