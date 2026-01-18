<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\EmailCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailCampaignController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EmailCampaign::class);
        $campaigns = EmailCampaign::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate();
        return response()->json($campaigns);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EmailCampaign::class);
        $validated = $request->validate([
            'name' => 'required|string',
            'subject' => 'required|string',
            'body' => 'required|string',
            'recipients' => 'required|array',
            'recipient_type' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);
        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['created_by'] = $request->user()->id;
        $validated['status'] = 'draft';
        $validated['total_recipients'] = count($validated['recipients']);
        $campaign = EmailCampaign::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Email campaign created successfully.',
            'data' => $campaign,
        ], 201);
    }

    public function show(EmailCampaign $emailCampaign): JsonResponse
    {
        $this->authorize('view', $emailCampaign);
        return response()->json([
            'success' => true,
            'data' => $emailCampaign,
        ]);
    }

    public function update(Request $request, EmailCampaign $emailCampaign): JsonResponse
    {
        $this->authorize('update', $emailCampaign);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'subject' => 'sometimes|string',
            'body' => 'sometimes|string',
            'recipients' => 'sometimes|array',
            'recipient_type' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'status' => 'sometimes|string|in:draft,scheduled,sending,completed,cancelled',
        ]);

        // Remove empty recipient_type
        if (isset($validated['recipient_type']) && empty(trim($validated['recipient_type']))) {
            $validated['recipient_type'] = null;
        }

        // Update total_recipients if recipients changed
        if (isset($validated['recipients'])) {
            $validated['total_recipients'] = count($validated['recipients']);
        }

        // Don't allow status changes through update unless explicitly provided
        // This prevents accidental status changes
        if (!isset($validated['status'])) {
            // Keep the current status if not provided
            unset($validated['status']);
        }

        $emailCampaign->update($validated);
        return response()->json([
            'success' => true,
            'message' => 'Email campaign updated successfully.',
            'data' => $emailCampaign->fresh(),
        ]);
    }

    public function destroy(EmailCampaign $emailCampaign): JsonResponse
    {
        $this->authorize('delete', $emailCampaign);
        $emailCampaign->delete();
        return response()->json([
            'success' => true,
            'message' => 'Email campaign deleted successfully.',
        ]);
    }

    public function send(EmailCampaign $emailCampaign): JsonResponse
    {
        $this->authorize('update', $emailCampaign);

        // Refresh the model to ensure we have the latest status
        $emailCampaign->refresh();
        
        // Normalize status (trim and lowercase for comparison)
        $status = strtolower(trim($emailCampaign->status ?? ''));
        
        if ($status !== 'draft' && $status !== 'scheduled') {
            \Illuminate\Support\Facades\Log::warning('Email campaign send attempt with invalid status', [
                'campaign_id' => $emailCampaign->id,
                'current_status' => $emailCampaign->status,
                'normalized_status' => $status,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Campaign can only be sent from draft or scheduled status. Current status: ' . ($emailCampaign->status ?? 'null'),
            ], 422);
        }

        $emailCampaign->update(['status' => 'sending']);

        // Get recipients
        $recipients = $emailCampaign->recipients ?? [];

        if (empty($recipients)) {
            $emailCampaign->update(['status' => 'draft']);
            return response()->json([
                'success' => false,
                'message' => 'No recipients found for this campaign.',
            ], 422);
        }

        // Dispatch job to send emails in background
        \App\Jobs\SendEmailCampaignJob::dispatch($emailCampaign, $recipients);

        return response()->json([
            'success' => true,
            'message' => 'Campaign sending started. Emails will be sent in the background.',
        ]);
    }
}

