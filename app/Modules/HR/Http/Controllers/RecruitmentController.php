<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreRecruitmentRequest;
use App\Modules\HR\Http\Requests\UpdateRecruitmentRequest;
use App\Modules\HR\Http\Resources\RecruitmentResource;
use App\Modules\HR\Models\Recruitment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecruitmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Recruitment::class);

        $query = Recruitment::with(['position'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }

        $recruitments = $query->latest()->paginate();

        return RecruitmentResource::collection($recruitments);
    }

    public function store(StoreRecruitmentRequest $request): JsonResponse
    {
        $this->authorize('create', Recruitment::class);

        $recruitment = Recruitment::create($request->validated());

        event(new EntityCreated($recruitment, $request->user()->id));

        return response()->json([
            'message' => 'Recruitment created successfully.',
            'data' => new RecruitmentResource($recruitment->load(['position'])),
        ], 201);
    }

    public function show(Recruitment $recruitment): JsonResponse
    {
        $this->authorize('view', $recruitment);

        return response()->json([
            'data' => new RecruitmentResource($recruitment->load(['position'])),
        ]);
    }

    public function update(UpdateRecruitmentRequest $request, Recruitment $recruitment): JsonResponse
    {
        $this->authorize('update', $recruitment);

        $recruitment->update($request->validated());

        event(new EntityUpdated($recruitment->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Recruitment updated successfully.',
            'data' => new RecruitmentResource($recruitment->load(['position'])),
        ]);
    }

    public function destroy(Recruitment $recruitment): JsonResponse
    {
        $this->authorize('delete', $recruitment);

        event(new EntityDeleted($recruitment, request()->user()->id));

        $recruitment->delete();

        return response()->json([
            'message' => 'Recruitment deleted successfully.',
        ]);
    }
}

