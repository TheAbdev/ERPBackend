<?php

namespace App\Core\Traits;

use App\Core\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for tracking model changes in audit logs.
 */
trait ModelChangeTracker
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootModelChangeTracker(): void
    {
        static::created(function (Model $model) {
            static::logModelChange($model, 'create', null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            static::logModelChange($model, 'update', $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function (Model $model) {
            static::logModelChange($model, 'delete', $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                static::logModelChange($model, 'restore', null, $model->getAttributes());
            });
        }
    }

    /**
     * Log model change.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $action
     * @param  array|null  $oldValues
     * @param  array|null  $newValues
     * @return void
     */
    protected static function logModelChange(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        // Only log if model uses BelongsToTenant trait
        if (! in_array(BelongsToTenant::class, class_uses_recursive($model))) {
            return;
        }

        // Skip if no tenant context
        if (! $model->tenant_id) {
            return;
        }

        try {
            $auditService = app(AuditService::class);
            $auditService->log($action, $model, $oldValues, $newValues);
        } catch (\Exception $e) {
            // Don't break the application if audit logging fails
            \Illuminate\Support\Facades\Log::error('Failed to log model change', [
                'model' => get_class($model),
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

