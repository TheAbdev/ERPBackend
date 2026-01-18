<?php

namespace App\Core\Services;

use App\Core\Models\AuditLog;
use Illuminate\Support\Collection;

/**
 * Service for building activity timelines.
 */
class ActivityTimelineService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get activity timeline for a model.
     *
     * @param  string  $modelType
     * @param  int  $modelId
     * @param  int|null  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getModelTimeline(string $modelType, int $modelId, ?int $limit = 50): Collection
    {
        $tenantId = $this->tenantContext->getTenantId();

        return AuditLog::where('tenant_id', $tenantId)
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            });
    }

    /**
     * Get activity timeline for a user.
     *
     * @param  int  $userId
     * @param  int|null  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getUserTimeline(int $userId, ?int $limit = 50): Collection
    {
        $tenantId = $this->tenantContext->getTenantId();

        return AuditLog::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'model_name' => $log->model_name,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            });
    }

    /**
     * Get recent activity for tenant.
     *
     * @param  int|null  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRecentActivity(?int $limit = 100): Collection
    {
        $tenantId = $this->tenantContext->getTenantId();

        return AuditLog::where('tenant_id', $tenantId)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'model_name' => $log->model_name,
                    'created_at' => $log->created_at->toDateTimeString(),
                ];
            });
    }
}

