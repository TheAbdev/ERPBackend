<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'journal_entry_id',
        'account_id',
        'currency_id',
        'debit',
        'credit',
        'amount_base',
        'description',
        'line_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'amount_base' => 'decimal:4',
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

        static::saving(function ($line) {
            // Calculate amount_base (always positive, represents the absolute value)
            $line->amount_base = abs($line->debit) + abs($line->credit);
        });
    }

    /**
     * Get the tenant that owns the journal entry line.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
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
     * Get the account.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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
}





