<?php

namespace App\Core\Traits;

use App\Core\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        // Automatically set tenant_id when creating a model
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $model->tenant_id = app(\App\Core\Services\TenantContext::class)->getTenantId();
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Scope a query to only include models for a specific tenant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}

