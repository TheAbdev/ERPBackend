<?php

namespace App\Observers;

use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Services\Workflows\WorkflowEngineService;
use App\Modules\CRM\Services\LeadAssignmentService;
use App\Core\Services\TenantContext;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        // Trigger workflow
        app(WorkflowEngineService::class)->trigger(
            'lead.created',
            $lead,
            ['old_status' => null, 'new_status' => $lead->status]
        );

        // Auto-assign lead if not already assigned
        if (!$lead->assigned_to) {
            $tenantContext = app(TenantContext::class);
            $tenantContext->setTenant($lead->tenant);
            
            $assignmentService = app(LeadAssignmentService::class);
            $assignedUserId = $assignmentService->autoAssign($lead);
            
            if ($assignedUserId) {
                // Log assignment in activity feed or audit log if needed
                \Illuminate\Support\Facades\Log::info('Lead auto-assigned', [
                    'lead_id' => $lead->id,
                    'assigned_to' => $assignedUserId,
                ]);
            }
        }
    }
}
