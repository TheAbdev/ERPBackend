<?php

namespace App\Modules\ERP\Traits;

use App\Modules\ERP\Models\FiscalPeriod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to a fiscal period.
 */
trait HasFiscalPeriod
{
    /**
     * Get the fiscal period for the model.
     *
     * @return BelongsTo
     */
    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    /**
     * Scope a query to only include records for a specific fiscal period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $fiscalPeriodId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForFiscalPeriod($query, int $fiscalPeriodId)
    {
        return $query->where('fiscal_period_id', $fiscalPeriodId);
    }

    /**
     * Scope a query to only include records for the active fiscal period.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForActiveFiscalPeriod($query)
    {
        $activePeriod = FiscalPeriod::where('tenant_id', $this->tenant_id ?? app(\App\Core\Services\TenantContext::class)->getTenantId())
            ->where('is_active', true)
            ->first();

        if ($activePeriod) {
            return $query->where('fiscal_period_id', $activePeriod->id);
        }

        return $query;
    }
}

