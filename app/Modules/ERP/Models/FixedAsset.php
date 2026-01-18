<?php

namespace App\Modules\ERP\Models;

use App\Modules\ERP\Traits\HasFiscalPeriod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends ErpBaseModel
{
    use HasFiscalPeriod;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'asset_code',
        'name',
        'description',
        'acquisition_date',
        'acquisition_cost',
        'salvage_value',
        'useful_life_months',
        'depreciation_method',
        'status',
        'asset_account_id',
        'depreciation_expense_account_id',
        'accumulated_depreciation_account_id',
        'currency_id',
        'fiscal_year_id',
        'fiscal_period_id',
        'activation_date',
        'disposal_date',
        'disposal_amount',
        'notes',
        'created_by',
        'activated_by',
        'disposed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'activation_date' => 'date',
            'disposal_date' => 'date',
            'acquisition_cost' => 'decimal:4',
            'salvage_value' => 'decimal:4',
            'disposal_amount' => 'decimal:4',
        ];
    }

    /**
     * Get the tenant that owns the asset.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the asset account.
     *
     * @return BelongsTo
     */
    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    /**
     * Get the depreciation expense account.
     *
     * @return BelongsTo
     */
    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    /**
     * Get the accumulated depreciation account.
     *
     * @return BelongsTo
     */
    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    /**
     * Get the currency.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the fiscal year.
     *
     * @return BelongsTo
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Get the user who created the asset.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who activated the asset.
     *
     * @return BelongsTo
     */
    public function activator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'activated_by');
    }

    /**
     * Get the user who disposed the asset.
     *
     * @return BelongsTo
     */
    public function disposer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'disposed_by');
    }

    /**
     * Get the asset depreciations.
     *
     * @return HasMany
     */
    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    /**
     * Check if asset is draft.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if asset is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if asset is disposed.
     *
     * @return bool
     */
    public function isDisposed(): bool
    {
        return $this->status === 'disposed';
    }

    /**
     * Calculate monthly depreciation amount (straight-line).
     *
     * @return float
     */
    public function calculateMonthlyDepreciation(): float
    {
        if ($this->useful_life_months <= 0) {
            return 0;
        }

        $depreciableAmount = $this->acquisition_cost - $this->salvage_value;
        return round($depreciableAmount / $this->useful_life_months, 2);
    }

    /**
     * Get total accumulated depreciation.
     *
     * @return float
     */
    public function getAccumulatedDepreciation(): float
    {
        return (float) $this->depreciations()
            ->where('is_posted', true)
            ->sum('amount');
    }

    /**
     * Get net book value.
     *
     * @return float
     */
    public function getNetBookValue(): float
    {
        return $this->acquisition_cost - $this->getAccumulatedDepreciation();
    }

    /**
     * Get remaining useful life in months.
     *
     * @return int
     */
    public function getRemainingUsefulLifeMonths(): int
    {
        $depreciatedMonths = $this->depreciations()->where('is_posted', true)->count();
        return max(0, $this->useful_life_months - $depreciatedMonths);
    }
}




