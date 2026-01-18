<?php

namespace App\Modules\CRM\Services\Reports;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadsReportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get total leads count.
     *
     * @param  array  $filters
     * @return int
     */
    public function getTotalLeads(array $filters = []): int
    {
        return $this->buildQuery($filters)->count();
    }

    /**
     * Get leads grouped by source.
     *
     * @param  array  $filters
     * @return array
     */
    public function getLeadsBySource(array $filters = []): array
    {
        return $this->buildQuery($filters)
            ->select('source', DB::raw('count(*) as count'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->source,
                    'count' => (int) $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get leads grouped by status.
     *
     * @param  array  $filters
     * @return array
     */
    public function getLeadsByStatus(array $filters = []): array
    {
        return $this->buildQuery($filters)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get conversion rate (leads converted to deals).
     *
     * @param  array  $filters
     * @return array
     */
    public function getConversionRate(array $filters = []): array
    {
        $totalLeads = $this->getTotalLeads($filters);

        $convertedLeads = Deal::where('tenant_id', $this->tenantContext->getTenantId())
            ->whereNotNull('lead_id')
            ->when(isset($filters['date_from']), function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->distinct('lead_id')
            ->count('lead_id');

        $conversionRate = $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;

        return [
            'total_leads' => $totalLeads,
            'converted_leads' => $convertedLeads,
            'conversion_rate' => round($conversionRate, 2),
        ];
    }

    /**
     * Build base query with filters.
     *
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder|\App\Modules\CRM\Models\Lead
     */
    protected function buildQuery(array $filters = [])
    {
        $query = Lead::where('tenant_id', $this->tenantContext->getTenantId());

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

        return $query;
    }
}

