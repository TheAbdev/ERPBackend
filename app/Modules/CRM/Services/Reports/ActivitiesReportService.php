<?php

namespace App\Modules\CRM\Services\Reports;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Activity;
use Illuminate\Support\Facades\DB;

class ActivitiesReportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get activities per user.
     *
     * @param  array  $filters
     * @return array
     */
    public function getActivitiesPerUser(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        return $query->select(
            'assigned_to',
            DB::raw('count(*) as total'),
            DB::raw('sum(case when status = "completed" then 1 else 0 end) as completed'),
            DB::raw('sum(case when status = "pending" then 1 else 0 end) as pending')
        )
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->get()
            ->map(function ($item) {
                $user = \App\Models\User::find($item->assigned_to);
                return [
                    'user_id' => $item->assigned_to,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'total' => (int) $item->total,
                    'completed' => (int) $item->completed,
                    'pending' => (int) $item->pending,
                ];
            })
            ->toArray();
    }

    /**
     * Get completed vs pending activities.
     *
     * @param  array  $filters
     * @return array
     */
    public function getCompletedVsPending(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        $completed = (clone $query)->where('status', 'completed')->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $canceled = (clone $query)->where('status', 'canceled')->count();

        $total = $completed + $pending + $canceled;

        return [
            'completed' => $completed,
            'pending' => $pending,
            'canceled' => $canceled,
            'total' => $total,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get overdue activities.
     *
     * @param  array  $filters
     * @return array
     */
    public function getOverdueActivities(array $filters = []): array
    {
        $query = $this->buildQuery($filters)
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());

        $overdue = $query->count();
        $overdueList = $query->with(['assignee', 'creator'])
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'subject' => $activity->subject,
                    'due_date' => $activity->due_date->toDateTimeString(),
                    'days_overdue' => now()->diffInDays($activity->due_date),
                    'assigned_to' => $activity->assignee ? $activity->assignee->name : null,
                ];
            })
            ->toArray();

        return [
            'count' => $overdue,
            'activities' => $overdueList,
        ];
    }

    /**
     * Get activity type distribution.
     *
     * @param  array  $filters
     * @return array
     */
    public function getActivityTypeDistribution(array $filters = []): array
    {
        return $this->buildQuery($filters)
            ->select('type', DB::raw('count(*) as count'))
            ->whereNotNull('type')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'count' => (int) $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get total activities count.
     *
     * @param  array  $filters
     * @return int
     */
    public function getTotalActivities(array $filters = []): int
    {
        return $this->buildQuery($filters)->count();
    }

    /**
     * Get activities grouped by type.
     *
     * @param  array  $filters
     * @return array
     */
    public function getByType(array $filters = []): array
    {
        return $this->buildQuery($filters)
            ->select('type', DB::raw('count(*) as count'))
            ->whereNotNull('type')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'count' => (int) $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get activities grouped by status.
     *
     * @param  array  $filters
     * @return array
     */
    public function getByStatus(array $filters = []): array
    {
        return $this->buildQuery($filters)
            ->select('status', DB::raw('count(*) as count'))
            ->whereNotNull('status')
            ->groupBy('status')
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
     * Get completion rate (percentage of completed activities).
     *
     * @param  array  $filters
     * @return float
     */
    public function getCompletionRate(array $filters = []): float
    {
        $query = $this->buildQuery($filters);
        
        $total = (clone $query)->count();
        if ($total === 0) {
            return 0;
        }

        $completed = (clone $query)->where('status', 'completed')->count();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Build base query with filters.
     *
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder|\App\Modules\CRM\Models\Activity
     */
    protected function buildQuery(array $filters = [])
    {
        $query = Activity::where('tenant_id', $this->tenantContext->getTenantId());

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

