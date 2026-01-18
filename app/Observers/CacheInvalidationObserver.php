<?php

namespace App\Observers;

use App\Core\Services\CacheService;
use App\Core\Traits\BelongsToTenant;

/**
 * Observer for automatic cache invalidation on model changes.
 */
class CacheInvalidationObserver
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle model created event.
     */
    public function created($model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Handle model updated event.
     */
    public function updated($model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Handle model deleted event.
     */
    public function deleted($model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Handle model restored event.
     */
    public function restored($model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Invalidate cache for a model.
     *
     * @param  mixed  $model
     * @return void
     */
    protected function invalidateModelCache($model): void
    {
        if (! in_array(BelongsToTenant::class, class_uses_recursive($model))) {
            return;
        }

        $tenantId = $model->tenant_id ?? null;
        if (! $tenantId) {
            return;
        }

        $modelName = strtolower(class_basename($model));

        // Invalidate model-specific caches
        $this->cacheService->forgetPattern("{$modelName}:*", $tenantId);
        $this->cacheService->forgetPattern("{$modelName}:count:*", $tenantId);
        $this->cacheService->forgetPattern("{$modelName}:with_relations:*", $tenantId);
        $this->cacheService->forgetPattern("{$modelName}:statistics:*", $tenantId);
    }
}

