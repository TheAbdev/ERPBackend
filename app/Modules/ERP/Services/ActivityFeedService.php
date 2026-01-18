<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\ActivityFeed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing activity feed.
 */
class ActivityFeedService extends BaseService
{
    /**
     * Log an action to activity feed.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  string  $action
     * @param  int|null  $userId
     * @param  array  $metadata
     * @return \App\Modules\ERP\Models\ActivityFeed
     */
    public function logAction(
        Model $entity,
        string $action,
        ?int $userId = null,
        array $metadata = []
    ): ActivityFeed {
        // Get tenant_id from entity
        $tenantId = $entity->tenant_id ?? $this->getTenantId();

        $activity = ActivityFeed::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId ?? (\Illuminate\Support\Facades\Auth::id()),
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'action' => $action,
            'metadata' => array_merge($metadata, [
                'entity_identifier' => $this->getEntityIdentifier($entity),
            ]),
        ]);

        // Clear activity feed cache
        $this->clearActivityFeedCache($tenantId);

        return $activity;
    }

    /**
     * Clear activity feed cache.
     *
     * @param  int  $tenantId
     * @return void
     */
    protected function clearActivityFeedCache(int $tenantId): void
    {
        // Clear common cache keys
        for ($limit = 10; $limit <= 100; $limit += 10) {
            \Illuminate\Support\Facades\Cache::forget("activity_feed_recent_{$tenantId}_{$limit}");
        }
    }

    /**
     * Fetch recent activities for tenant.
     *
     * @param  int|null  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function fetchRecent(?int $limit = 50): Collection
    {
        $cacheKey = "activity_feed_recent_{$this->getTenantId()}_{$limit}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($limit) {
            return ActivityFeed::where('tenant_id', $this->getTenantId())
                ->with(['user', 'entity'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Fetch activities for a specific entity.
     *
     * @param  string  $entityType
     * @param  int  $entityId
     * @param  int|null  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function fetchForEntity(string $entityType, int $entityId, ?int $limit = 50): Collection
    {
        return ActivityFeed::where('tenant_id', $this->getTenantId())
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with(['user', 'entity'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get entity identifier for display.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return string
     */
    protected function getEntityIdentifier(Model $entity): string
    {
        $identifierFields = [
            'number', 'code', 'invoice_number', 'payment_number',
            'entry_number', 'order_number', 'asset_code', 'name', 'title'
        ];

        foreach ($identifierFields as $field) {
            if (isset($entity->$field)) {
                return (string) $entity->$field;
            }
        }

        return "#{$entity->id}";
    }
}

