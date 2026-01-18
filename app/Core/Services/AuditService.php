<?php

namespace App\Core\Services;

use App\Core\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    protected TenantContext $tenantContext;
    protected LogMaskingService $logMaskingService;

    public function __construct(
        TenantContext $tenantContext,
        LogMaskingService $logMaskingService
    ) {
        $this->tenantContext = $tenantContext;
        $this->logMaskingService = $logMaskingService;
    }

    /**
     * Log an audit event.
     *
     * @param  string  $action
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @param  array|null  $oldValues
     * @param  array|null  $newValues
     * @param  array|null  $metadata
     * @param  \App\Models\User|null  $user
     * @return \App\Core\Models\AuditLog
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?\App\Models\User $user = null
    ): AuditLog {
        $tenantId = $this->tenantContext->getTenantId();
        $user = $user ?? (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user() : null);

        // Mask sensitive data
        $oldValues = $oldValues ? $this->logMaskingService->mask($oldValues) : null;
        $newValues = $newValues ? $this->logMaskingService->mask($newValues) : null;

        $modelType = $model ? get_class($model) : null;
        $modelId = $model?->id;
        $modelName = $model ? class_basename($model) : null;

        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'model_name' => $modelName,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => array_merge($metadata ?? [], [
                'url' => Request::fullUrl(),
                'method' => Request::method(),
            ]),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'request_id' => Request::header('X-Request-ID') ?? uniqid('req_', true),
        ]);
    }

    /**
     * Log authentication event.
     *
     * @param  string  $action
     * @param  \App\Models\User  $user
     * @param  array|null  $metadata
     * @return \App\Core\Models\AuditLog
     */
    public function logAuth(string $action, \App\Models\User $user, ?array $metadata = null): AuditLog
    {
        return $this->log($action, null, null, null, $metadata, $user);
    }

    /**
     * Log import/export event.
     *
     * @param  string  $action
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array|null  $metadata
     * @return \App\Core\Models\AuditLog
     */
    public function logImportExport(string $action, Model $model, ?array $metadata = null): AuditLog
    {
        return $this->log($action, $model, null, null, $metadata);
    }
}

