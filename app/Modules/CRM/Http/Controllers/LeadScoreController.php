<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Services\LeadScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadScoreController extends Controller
{
    protected LeadScoringService $scoringService;

    public function __construct(LeadScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Calculate score for a lead.
     */
    public function calculate(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $score = $this->scoringService->calculateScore($lead);

        return response()->json([
            'success' => true,
            'message' => 'Lead score calculated successfully.',
            'data' => [
                'lead_id' => $lead->id,
                'score' => $score,
            ],
        ]);
    }

    /**
     * Recalculate scores for all leads.
     */
    public function recalculateAll(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $this->scoringService->recalculateAll();

        return response()->json([
            'success' => true,
            'message' => 'All lead scores recalculated successfully.',
        ]);
    }
}

