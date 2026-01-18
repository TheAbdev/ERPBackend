<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberSequence extends ErpBaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'prefix',
        'suffix',
        'next_number',
        'min_length',
        'format',
        'reset_period',
        'reset_frequency',
        'last_reset_date',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'min_length' => 'integer',
            'reset_period' => 'boolean',
            'is_active' => 'boolean',
            'last_reset_date' => 'date',
        ];
    }

    /**
     * Get the tenant that owns the number sequence.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }
}

