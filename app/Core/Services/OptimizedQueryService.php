<?php

namespace App\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Service for optimized query patterns.
 *
 * This service provides examples and utilities for writing
 * performant queries in a multi-tenant environment.
 */
class OptimizedQueryService
{
    protected CacheService $cacheService;
    protected TenantContext $tenantContext;

    public function __construct(CacheService $cacheService, TenantContext $tenantContext)
    {
        $this->cacheService = $cacheService;
        $this->tenantContext = $tenantContext;
    }

    /**
     * Example: Optimized query with eager loading and caching.
     *
     * @param  array  $filters
     * @return \Illuminate\Support\Collection
     */
    public function getLeadsWithRelations(array $filters = []): Collection
    {
        $tenantId = $this->tenantContext->getTenantId();
        $cacheKey = 'leads:with_relations:'.md5(json_encode($filters));

        return $this->cacheService->remember(
            $cacheKey,
            config('performance.cache.default_ttl', 3600),
            function () use ($tenantId, $filters) {
                return \App\Modules\CRM\Models\Lead::where('tenant_id', $tenantId)
                    ->with(['assignee:id,name,email', 'creator:id,name']) // Eager load only needed fields
                    ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
                    ->when(isset($filters['assigned_to']), fn ($q) => $q->where('assigned_to', $filters['assigned_to']))
                    ->select(['id', 'name', 'email', 'status', 'assigned_to', 'created_by', 'created_at']) // Select only needed columns
                    ->orderBy('created_at', 'desc')
                    ->limit(config('performance.query.max_result_size', 1000))
                    ->get();
            },
            $tenantId
        );
    }

    /**
     * Example: Optimized pagination query.
     *
     * @param  int  $perPage
     * @param  array  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedDeals(int $perPage = 15, array $filters = [])
    {
        $tenantId = $this->tenantContext->getTenantId();
        $perPage = min($perPage, config('performance.api.max_pagination_size', 100));

        $query = \App\Modules\CRM\Models\Deal::where('tenant_id', $tenantId)
            ->with([
                'pipeline:id,name',
                'stage:id,name,position',
                'assignee:id,name',
            ])
            ->select([
                'id', 'title', 'amount', 'status', 'pipeline_id', 'stage_id',
                'assigned_to', 'expected_close_date', 'created_at',
            ])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['pipeline_id']), fn ($q) => $q->where('pipeline_id', $filters['pipeline_id']))
            ->when(isset($filters['assigned_to']), fn ($q) => $q->where('assigned_to', $filters['assigned_to']))
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Example: Optimized aggregation query.
     *
     * @param  array  $filters
     * @return array
     */
    public function getDealStatistics(array $filters = []): array
    {
        $tenantId = $this->tenantContext->getTenantId();
        $cacheKey = 'deals:statistics:'.md5(json_encode($filters));

        return $this->cacheService->remember(
            $cacheKey,
            config('performance.cache.report_cache_ttl', 900),
            function () use ($tenantId, $filters) {
                $query = \App\Modules\CRM\Models\Deal::where('tenant_id', $tenantId)
                    ->when(isset($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
                    ->when(isset($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']));

                return [
                    'total' => $query->count(),
                    'total_value' => $query->sum('amount'),
                    'won' => (clone $query)->where('status', 'won')->count(),
                    'lost' => (clone $query)->where('status', 'lost')->count(),
                    'open' => (clone $query)->where('status', 'open')->count(),
                ];
            },
            $tenantId
        );
    }

    /**
     * Example: Optimized count query with caching.
     *
     * @param  string  $model
     * @param  array  $filters
     * @return int
     */
    public function getCachedCount(string $model, array $filters = []): int
    {
        $tenantId = $this->tenantContext->getTenantId();
        $cacheKey = strtolower(class_basename($model)).':count:'.md5(json_encode($filters));

        return $this->cacheService->remember(
            $cacheKey,
            config('performance.cache.default_ttl', 3600),
            function () use ($model, $tenantId, $filters) {
                $query = $model::where('tenant_id', $tenantId);

                foreach ($filters as $key => $value) {
                    $query->where($key, $value);
                }

                return $query->count();
            },
            $tenantId
        );
    }
}

