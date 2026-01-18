<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\MoveDealStageRequest;
use App\Modules\CRM\Http\Requests\StoreDealRequest;
use App\Modules\CRM\Http\Requests\UpdateDealRequest;
use App\Modules\CRM\Http\Resources\DealResource;
use App\Modules\CRM\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Deal::class);

        $deals = Deal::with(['pipeline', 'stage', 'lead', 'contact', 'account', 'creator', 'assignee'])
            ->latest()
            ->paginate();

        return DealResource::collection($deals);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StoreDealRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDealRequest $request): JsonResponse
    {
        $this->authorize('create', Deal::class);

        $deal = Deal::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status' => 'open',
        ]);

        $deal->load(['pipeline', 'stage', 'lead', 'contact', 'account', 'creator', 'assignee']);

        return response()->json([
            'data' => new DealResource($deal),
            'message' => 'Deal created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Deal  $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Deal $deal): JsonResponse
    {
        $this->authorize('view', $deal);

        $deal->load(['pipeline', 'stage', 'lead', 'contact', 'account', 'creator', 'assignee', 'histories.user']);

        return response()->json([
            'data' => new DealResource($deal),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdateDealRequest  $request
     * @param  \App\Modules\CRM\Models\Deal  $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateDealRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $deal->update($request->validated());

        // Log update
        $deal->logHistory('updated', [
            'changes' => $request->validated(),
        ]);

        $deal->load(['pipeline', 'stage', 'lead', 'contact', 'account', 'creator', 'assignee']);

        return response()->json([
            'data' => new DealResource($deal),
            'message' => 'Deal updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Deal  $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Deal $deal): JsonResponse
    {
        $this->authorize('delete', $deal);

        $deal->delete();

        return response()->json([
            'message' => 'Deal deleted successfully.',
        ]);
    }

    /**
     * Move deal to a different stage.
     *
     * @param  \App\Modules\CRM\Http\Requests\MoveDealStageRequest  $request
     * @param  \App\Modules\CRM\Models\Deal  $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveStage(MoveDealStageRequest $request, Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $oldStageId = $deal->stage_id;
        $deal->update(['stage_id' => $request->stage_id]);

        // Update probability from stage
        $stage = $deal->stage;
        if ($stage) {
            $deal->update(['probability' => $stage->probability]);
        }

        $deal->load(['pipeline', 'stage', 'lead', 'contact', 'account', 'creator', 'assignee']);

        return response()->json([
            'data' => new DealResource($deal),
            'message' => 'Deal moved to new stage successfully.',
        ]);
    }

    /**
     * Mark deal as won.
     *
     * @param  \App\Modules\CRM\Models\Deal  $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function markWon(Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $deal->update([
            'status' => 'won',
            'probability' => 100,
        ]);

        // Event is dispatched automatically in model boot

        $deal->load(['pipeline', 'stage', 'lead', 'contact', 'account', 'creator', 'assignee']);

        return response()->json([
            'data' => new DealResource($deal),
            'message' => 'Deal marked as won.',
        ]);
    }

    /**
     * Mark deal as lost.
     *
     * @param  \App\Modules\CRM\Models\Deal  $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function markLost(Deal $deal): JsonResponse
    {
        $this->authorize('update', $deal);

        $deal->update([
            'status' => 'lost',
            'probability' => 0,
        ]);

        // Event is dispatched automatically in model boot

        $deal->load(['pipeline', 'stage', 'lead', 'contact', 'account', 'creator', 'assignee']);

        return response()->json([
            'data' => new DealResource($deal),
            'message' => 'Deal marked as lost.',
        ]);
    }
}

