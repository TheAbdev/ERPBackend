<?php

namespace App\Modules\CRM\Services\Export;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Lead;

class LeadExportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Export leads with filters.
     *
     * @param  array  $filters
     * @return array
     */
    public function export(array $filters = []): array
    {
        $query = Lead::where('tenant_id', $this->tenantContext->getTenantId());

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

        $leads = $query->get();

        return $leads->map(function ($lead) {
            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'source' => $lead->source,
                'status' => $lead->status,
                'assigned_to' => $lead->assignee ? $lead->assignee->name : null,
                'created_by' => $lead->creator ? $lead->creator->name : null,
                'created_at' => $lead->created_at->toDateTimeString(),
            ];
        })->toArray();
    }
}

