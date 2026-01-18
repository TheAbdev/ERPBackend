<?php

namespace App\Modules\CRM\Services\Export;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Deal;

class DealExportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Export deals with filters.
     *
     * @param  array  $filters
     * @return array
     */
    public function export(array $filters = []): array
    {
        $query = Deal::where('tenant_id', $this->tenantContext->getTenantId())
            ->with(['pipeline', 'stage', 'lead', 'contact', 'account', 'assignee', 'creator']);

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

        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        $deals = $query->get();

        return $deals->map(function ($deal) {
            return [
                'id' => $deal->id,
                'title' => $deal->title,
                'amount' => $deal->amount,
                'currency' => $deal->currency,
                'probability' => $deal->probability,
                'status' => $deal->status,
                'pipeline' => $deal->pipeline ? $deal->pipeline->name : null,
                'stage' => $deal->stage ? $deal->stage->name : null,
                'lead' => $deal->lead ? $deal->lead->name : null,
                'contact' => $deal->contact ? $deal->contact->first_name.' '.$deal->contact->last_name : null,
                'account' => $deal->account ? $deal->account->name : null,
                'assigned_to' => $deal->assignee ? $deal->assignee->name : null,
                'created_by' => $deal->creator ? $deal->creator->name : null,
                'expected_close_date' => $deal->expected_close_date ? $deal->expected_close_date->toDateString() : null,
                'created_at' => $deal->created_at->toDateTimeString(),
            ];
        })->toArray();
    }
}

