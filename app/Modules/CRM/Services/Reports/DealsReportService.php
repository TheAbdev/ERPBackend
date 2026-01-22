<?php

namespace App\Modules\CRM\Services\Reports;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Pipeline;
use App\Modules\CRM\Models\PipelineStage;
use Illuminate\Support\Facades\DB;

class DealsReportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get total deal value.
     *
     * @param  array  $filters
     * @return array
     */
    public function getTotalDealValue(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        $total = $query->sum('amount');
        $count = $query->count();

        return [
            'total_value' => (float) $total,
            'deal_count' => $count,
            'average_deal_size' => $count > 0 ? round($total / $count, 2) : 0,
        ];
    }

    /**
     * Get won vs lost deals.
     *
     * @param  array  $filters
     * @return array
     */
    public function getWonVsLost(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        $won = (clone $query)->where('status', 'won')->sum('amount');
        $lost = (clone $query)->where('status', 'lost')->sum('amount');
        $open = (clone $query)->where('status', 'open')->sum('amount');

        $wonCount = (clone $query)->where('status', 'won')->count();
        $lostCount = (clone $query)->where('status', 'lost')->count();
        $openCount = (clone $query)->where('status', 'open')->count();

        return [
            'won' => [
                'count' => $wonCount,
                'value' => (float) $won,
            ],
            'lost' => [
                'count' => $lostCount,
                'value' => (float) $lost,
            ],
            'open' => [
                'count' => $openCount,
                'value' => (float) $open,
            ],
        ];
    }

    /**
     * Get deal pipeline funnel.
     *
     * @param  array  $filters
     * @return array
     */
    public function getPipelineFunnel(array $filters = []): array
    {
        $pipelineId = $filters['pipeline_id'] ?? null;

        if ($pipelineId) {
            $pipeline = Pipeline::where('tenant_id', $this->tenantContext->getTenantId())
                ->find($pipelineId);

            if (! $pipeline) {
                return [];
            }

            $stages = $pipeline->stages()->orderBy('position')->get();
        } else {
            // Get default pipeline or first pipeline
            $pipeline = Pipeline::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('is_default', true)
                ->first();

            if (! $pipeline) {
                $pipeline = Pipeline::where('tenant_id', $this->tenantContext->getTenantId())
                    ->first();
            }

            if (! $pipeline) {
                return [];
            }

            $stages = $pipeline->stages()->orderBy('position')->get();
        }

        $funnel = [];

        foreach ($stages as $stage) {
            $query = Deal::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('stage_id', $stage->id)
                ->where('status', 'open');

            if (isset($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $count = $query->count();
            $value = $query->sum('amount');

            $funnel[] = [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'position' => $stage->position,
                'deal_count' => $count,
                'deal_value' => (float) $value,
            ];
        }

        return $funnel;
    }

    /**
     * Get average deal duration.
     *
     * @param  array  $filters
     * @return array
     */
    public function getAverageDealDuration(array $filters = []): array
    {
        $query = Deal::where('tenant_id', $this->tenantContext->getTenantId())
            ->whereIn('status', ['won', 'lost'])
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at');

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        $deals = $query->get();

        if ($deals->isEmpty()) {
            return [
                'average_days' => 0,
                'min_days' => 0,
                'max_days' => 0,
            ];
        }

        $durations = $deals->map(function ($deal) {
            return $deal->created_at->diffInDays($deal->updated_at);
        });

        return [
            'average_days' => round($durations->avg(), 2),
            'min_days' => $durations->min(),
            'max_days' => $durations->max(),
        ];
    }

    /**
     * Get revenue forecast based on expected close dates.
     *
     * @param  array  $filters
     * @return array
     */
    public function getRevenueForecast(array $filters = []): array
    {
        $query = Deal::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('status', 'open')
            ->whereNotNull('expected_close_date')
            ->whereNotNull('amount');

        if (isset($filters['date_from'])) {
            $query->whereDate('expected_close_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('expected_close_date', '<=', $filters['date_to']);
        }

        if (isset($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        $forecast = $query->select(
            DB::raw('DATE(expected_close_date) as date'),
            DB::raw('SUM(amount) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'forecasted_revenue' => (float) $item->total,
                    'deal_count' => (int) $item->count,
                ];
            })
            ->toArray();

        $totalForecast = array_sum(array_column($forecast, 'forecasted_revenue'));

        return [
            'forecast_by_date' => $forecast,
            'total_forecasted_revenue' => $totalForecast,
        ];
    }

    /**
     * Get total deals count.
     *
     * @param  array  $filters
     * @return int
     */
    public function getTotalDeals(array $filters = []): int
    {
        return $this->buildQuery($filters)->count();
    }

    /**
     * Get deals grouped by stage.
     *
     * @param  array  $filters
     * @return array
     */
    public function getByStage(array $filters = []): array
    {
        $query = Deal::where('deals.tenant_id', $this->tenantContext->getTenantId())
            ->join('pipeline_stages', 'deals.stage_id', '=', 'pipeline_stages.id')
            ->select(
                'pipeline_stages.name as stage',
                DB::raw('COUNT(deals.id) as count'),
                DB::raw('COALESCE(SUM(deals.amount), 0) as value')
            );

        if (isset($filters['date_from'])) {
            $query->whereDate('deals.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('deals.created_at', '<=', $filters['date_to']);
        }

        return $query->groupBy('pipeline_stages.id', 'pipeline_stages.name')
            ->get()
            ->map(function ($item) {
                return [
                    'stage' => $item->stage,
                    'count' => (int) $item->count,
                    'value' => (float) $item->value,
                ];
            })
            ->toArray();
    }

    /**
     * Get deals grouped by status.
     *
     * @param  array  $filters
     * @return array
     */
    public function getByStatus(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->select(
                'status',
                DB::raw('COUNT(id) as count'),
                DB::raw('COALESCE(SUM(amount), 0) as value')
            );

        return $query->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status ?? 'unknown',
                    'count' => (int) $item->count,
                    'value' => (float) $item->value,
                ];
            })
            ->toArray();
    }

    /**
     * Get win rate (percentage of won deals).
     *
     * @param  array  $filters
     * @return float
     */
    public function getWinRate(array $filters = []): float
    {
        $query = $this->buildQuery($filters);

        $total = (clone $query)->count();
        if ($total === 0) {
            return 0;
        }

        $won = (clone $query)->where('status', 'won')->count();

        return round(($won / $total) * 100, 2);
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
            $query->where(function ($q) use ($filters) {
                $q->where('assigned_to', $filters['user_id'])
                  ->orWhere('created_by', $filters['user_id']);
            });
        }

        return $query;
    }
}

