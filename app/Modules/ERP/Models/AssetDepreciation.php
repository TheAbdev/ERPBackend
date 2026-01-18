<?php

namespace App\Modules\ERP\Models;

use App\Modules\ERP\Traits\HasFiscalPeriod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDepreciation extends ErpBaseModel
{
    use HasFiscalPeriod;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'fixed_asset_id',
        'fiscal_year_id',
        'fiscal_period_id',
        'depreciation_date',
        'amount',
        'journal_entry_id',
        'is_posted',
        'posted_by',
        'posted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depreciation_date' => 'date',
            'amount' => 'decimal:4',
            'is_posted' => 'boolean',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the depreciation.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the fixed asset.
     *
     * @return BelongsTo
     */
    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
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
     * Get the journal entry.
     *
     * @return BelongsTo
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the user who posted the depreciation.
     *
     * @return BelongsTo
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by');
    }

    /**
     * Check if depreciation is posted.
     *
     * @return bool
     */
    public function isPosted(): bool
    {
        return $this->is_posted;
    }
}




