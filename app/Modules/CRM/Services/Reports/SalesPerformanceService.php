<?php

namespace App\Modules\CRM\Services\Reports;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Deal;
use Illuminate\Support\Facades\DB;

class SalesPerformanceService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get revenue per user.
     *
     * @param  array  $filters
     * @return array
     */
    public function getRevenuePerUser(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->where('status', 'won')
            ->whereNotNull('assigned_to');

        return $query->select(
            'assigned_to',
            DB::raw('SUM(amount) as total_revenue'),
            DB::raw('COUNT(*) as deals_closed')
        )
            ->groupBy('assigned_to')
            ->orderBy('total_revenue', 'desc')
            ->get()
            ->map(function ($item) {
                $user = \App\Models\User::find($item->assigned_to);
                return [
                    'user_id' => $item->assigned_to,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'total_revenue' => (float) $item->total_revenue,
                    'deals_closed' => (int) $item->deals_closed,
                ];
            })
            ->toArray();
    }

    /**
     * Get deals closed per user.
     *
     * @param  array  $filters
     * @return array
     */
    public function getDealsClosedPerUser(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->whereIn('status', ['won', 'lost'])
            ->whereNotNull('assigned_to');

        return $query->select(
            'assigned_to',
            DB::raw('COUNT(*) as total_deals'),
            DB::raw('SUM(case when status = "won" then 1 else 0 end) as won'),
            DB::raw('SUM(case when status = "lost" then 1 else 0 end) as lost')
        )
            ->groupBy('assigned_to')
            ->orderBy('total_deals', 'desc')
            ->get()
            ->map(function ($item) {
                $user = \App\Models\User::find($item->assigned_to);
                $winRate = $item->total_deals > 0
                    ? round(($item->won / $item->total_deals) * 100, 2)
                    : 0;

                return [
                    'user_id' => $item->assigned_to,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'total_deals' => (int) $item->total_deals,
                    'won' => (int) $item->won,
                    'lost' => (int) $item->lost,
                    'win_rate' => $winRate,
                ];
            })
            ->toArray();
    }

    /**
     * Get average deal size per user.
     *
     * @param  array  $filters
     * @return array
     */
    public function getAverageDealSizePerUser(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->where('status', 'won')
            ->whereNotNull('assigned_to')
            ->whereNotNull('amount');

        return $query->select(
            'assigned_to',
            DB::raw('AVG(amount) as average_deal_size'),
            DB::raw('MIN(amount) as min_deal_size'),
            DB::raw('MAX(amount) as max_deal_size'),
            DB::raw('COUNT(*) as deal_count')
        )
            ->groupBy('assigned_to')
            ->orderBy('average_deal_size', 'desc')
            ->get()
            ->map(function ($item) {
                $user = \App\Models\User::find($item->assigned_to);
                return [
                    'user_id' => $item->assigned_to,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'average_deal_size' => round((float) $item->average_deal_size, 2),
                    'min_deal_size' => (float) $item->min_deal_size,
                    'max_deal_size' => (float) $item->max_deal_size,
                    'deal_count' => (int) $item->deal_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get win rate per user.
     *
     * @param  array  $filters
     * @return array
     */
    public function getWinRatePerUser(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->whereIn('status', ['won', 'lost'])
            ->whereNotNull('assigned_to');

        return $query->select(
            'assigned_to',
            DB::raw('COUNT(*) as total_deals'),
            DB::raw('SUM(case when status = "won" then 1 else 0 end) as won')
        )
            ->groupBy('assigned_to')
            ->orderBy('won', 'desc')
            ->get()
            ->map(function ($item) {
                $user = \App\Models\User::find($item->assigned_to);
                $winRate = $item->total_deals > 0
                    ? round(($item->won / $item->total_deals) * 100, 2)
                    : 0;

                return [
                    'user_id' => $item->assigned_to,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'total_deals' => (int) $item->total_deals,
                    'won' => (int) $item->won,
                    'win_rate' => $winRate,
                ];
            })
            ->toArray();
    }

    /**
     * Get total revenue from won deals.
     *
     * @param  array  $filters
     * @return float
     */
    public function getTotalRevenue(array $filters = []): float
    {
        $query = $this->buildQuery($filters)->where('status', 'won');
        return (float) ($query->sum('amount') ?? 0);
    }

    /**
     * Get revenue grouped by period.
     *
     * @param  array  $filters
     * @return array
     */
    public function getByPeriod(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->where('status', 'won')
            ->select(
                DB::raw('DATE(created_at) as period'),
                DB::raw('SUM(amount) as revenue')
            );

        return $query->groupBy('period')
            ->orderBy('period', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->period,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * Get sales by user.
     *
     * @param  array  $filters
     * @return array
     */
    public function getByUser(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->where('status', 'won')
            ->whereNotNull('assigned_to')
            ->select(
                'assigned_to',
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as deals_count')
            );

        return $query->groupBy('assigned_to')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(function ($item) {
                $user = \App\Models\User::find($item->assigned_to);
                return [
                    'user' => $user ? $user->name : 'Unknown',
                    'revenue' => (float) $item->revenue,
                    'deals_count' => (int) $item->deals_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get average deal size from won deals.
     *
     * @param  array  $filters
     * @return float
     */
    public function getAverageDealSize(array $filters = []): float
    {
        $query = $this->buildQuery($filters)->where('status', 'won');
        
        $total = (float) ($query->sum('amount') ?? 0);
        $count = (clone $query)->count();

        return $count > 0 ? round($total / $count, 2) : 0;
    }

    /**
     * Build base query with filters.
     *
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder|\App\Modules\CRM\Models\Deal
     */
    protected function buildQuery(array $filters = [])
    {
        $query = Deal::where('tenant_id', $this->tenantContext->getTenantId());

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('assigned_to', $filters['user_id']);
        }

        return $query;
    }
}

