<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Services\LeadConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadConversionController extends Controller
{
    protected LeadConversionService $conversionService;

    public function __construct(LeadConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * Convert lead to contact.
     */
    public function toContact(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $contact = $this->conversionService->convertToContact($lead, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Lead converted to contact successfully.',
            'data' => $contact,
        ]);
    }

    /**
     * Convert lead to deal.
     */
    public function toDeal(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'pipeline_id' => 'nullable|exists:pipelines,id',
            'stage_id' => 'nullable|exists:pipeline_stages,id',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
        ]);

        $deal = $this->conversionService->convertToDeal($lead, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Lead converted to deal successfully.',
            'data' => $deal,
        ]);
    }

    /**
     * Convert lead to both contact and deal.
     */
    public function toContactAndDeal(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $request->validate([
            'contact' => 'nullable|array',
            'contact.first_name' => 'nullable|string|max:255',
            'contact.last_name' => 'nullable|string|max:255',
            'deal' => 'required|array',
            'deal.name' => 'required|string|max:255',
            'deal.amount' => 'nullable|numeric|min:0',
        ]);

        $result = $this->conversionService->convertToContactAndDeal(
            $lead,
            $request->input('contact', []),
            $request->input('deal', [])
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead converted to contact and deal successfully.',
            'data' => $result,
        ]);
    }
}

