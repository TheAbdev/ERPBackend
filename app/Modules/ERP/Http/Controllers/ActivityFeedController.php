<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\ActivityFeedResource;
use App\Modules\ERP\Services\ActivityFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityFeedController extends Controller
{
    protected ActivityFeedService $activityFeedService;

    public function __construct(ActivityFeedService $activityFeedService)
    {
        $this->activityFeedService = $activityFeedService;
    }

    /**
     * Display a listing of recent activities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\ActivityFeed::class);

        $limit = $request->input('limit', 50);

        $activities = $this->activityFeedService->fetchRecent($limit);

        return ActivityFeedResource::collection($activities);
    }

    /**
     * Fetch activities for a specific entity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $entityType
     * @param  int  $entityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function entity(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $this->authorize('viewAny', \App\Modules\ERP\Models\ActivityFeed::class);

        $limit = $request->input('limit', 50);

        // Decode entity type if URL encoded
        $entityType = urldecode($entityType);

        $activities = $this->activityFeedService->fetchForEntity($entityType, $entityId, $limit);

        return response()->json([
            'data' => ActivityFeedResource::collection($activities),
        ]);
    }
}

