<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StorePerformanceReviewRequest;
use App\Modules\HR\Http\Requests\UpdatePerformanceReviewRequest;
use App\Modules\HR\Http\Resources\PerformanceReviewResource;
use App\Modules\HR\Models\PerformanceReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PerformanceReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PerformanceReview::class);

        $query = PerformanceReview::with(['employee', 'reviewer'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $reviews = $query->latest()->paginate();

        return PerformanceReviewResource::collection($reviews);
    }

    public function store(StorePerformanceReviewRequest $request): JsonResponse
    {
        $this->authorize('create', PerformanceReview::class);

        $review = PerformanceReview::create($request->validated());

        event(new EntityCreated($review, $request->user()->id));

        return response()->json([
            'message' => 'Performance review created successfully.',
            'data' => new PerformanceReviewResource($review->load(['employee', 'reviewer'])),
        ], 201);
    }

    public function show(PerformanceReview $performanceReview): JsonResponse
    {
        $this->authorize('view', $performanceReview);

        return response()->json([
            'data' => new PerformanceReviewResource($performanceReview->load(['employee', 'reviewer'])),
        ]);
    }

    public function update(UpdatePerformanceReviewRequest $request, PerformanceReview $performanceReview): JsonResponse
    {
        $this->authorize('update', $performanceReview);

        $performanceReview->update($request->validated());

        event(new EntityUpdated($performanceReview->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Performance review updated successfully.',
            'data' => new PerformanceReviewResource($performanceReview->load(['employee', 'reviewer'])),
        ]);
    }

    public function destroy(PerformanceReview $performanceReview): JsonResponse
    {
        $this->authorize('delete', $performanceReview);

        event(new EntityDeleted($performanceReview, request()->user()->id));

        $performanceReview->delete();

        return response()->json([
            'message' => 'Performance review deleted successfully.',
        ]);
    }
}

