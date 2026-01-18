<?php

namespace App\Modules\CRM\Services\Export;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Activity;

class ActivityExportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Export activities with filters.
     *
     * @param  array  $filters
     * @return array
     */
    public function export(array $filters = []): array
    {
        $query = Activity::where('tenant_id', $this->tenantContext->getTenantId())
            ->with(['assignee', 'creator', 'related']);

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('assigned_to', $filters['user_id'])
                  ->orWhere('created_by', $filters['user_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $activities = $query->get();

        return $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->type,
                'subject' => $activity->subject,
                'description' => $activity->description,
                'due_date' => $activity->due_date ? $activity->due_date->toDateTimeString() : null,
                'priority' => $activity->priority,
                'status' => $activity->status,
                'related_type' => $activity->related_type,
                'related_id' => $activity->related_id,
                'assigned_to' => $activity->assignee ? $activity->assignee->name : null,
                'created_by' => $activity->creator ? $activity->creator->name : null,
                'created_at' => $activity->created_at->toDateTimeString(),
            ];
        })->toArray();
    }
}

