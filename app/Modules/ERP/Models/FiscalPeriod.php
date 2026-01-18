<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'fiscal_year_id',
        'name',
        'code',
        'start_date',
        'end_date',
        'period_number',
        'is_active',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'period_number' => 'integer',
            'is_active' => 'boolean',
            'is_closed' => 'boolean',
            'closed_at' => 'date',
        ];
    }

    /**
     * Get the tenant that owns the fiscal period.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the fiscal year that owns the fiscal period.
     *
     * @return BelongsTo
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Get the user who closed the fiscal period.
     *
     * @return BelongsTo
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'closed_by');
    }
}

