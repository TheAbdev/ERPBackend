<?php

namespace App\Modules\ERP\Models;

use App\Modules\ERP\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends ErpBaseModel
{
    use HasDocumentNumber;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'entry_number',
        'fiscal_year_id',
        'fiscal_period_id',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'created_by',
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
            'entry_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($entry) {
            if (empty($entry->entry_number)) {
                $entry->entry_number = $entry->generateDocumentNumber('journal_entry');
            }
        });
    }

    /**
     * Get the tenant that owns the journal entry.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
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
     * Get the fiscal period.
     *
     * @return BelongsTo
     */
    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    /**
     * Get the user who created the entry.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who posted the entry.
     *
     * @return BelongsTo
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by');
    }

    /**
     * Get the journal entry lines.
     *
     * @return HasMany
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get the reference model (polymorphic).
     *
     * @return MorphTo
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Check if entry is posted.
     *
     * @return bool
     */
    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Check if entry is draft.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Get total debits.
     *
     * @return float
     */
    public function getTotalDebits(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    /**
     * Get total credits.
     *
     * @return float
     */
    public function getTotalCredits(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    /**
     * Check if entry is balanced.
     *
     * @return bool
     */
    public function isBalanced(): bool
    {
        return abs($this->getTotalDebits() - $this->getTotalCredits()) < 0.01; // Allow small rounding differences
    }
}

