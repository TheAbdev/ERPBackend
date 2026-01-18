<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringInvoice extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_address',
        'currency_id',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'occurrences',
        'day_of_month',
        'day_of_week',
        'next_run_date',
        'last_run_date',
        'generated_count',
        'is_active',
        'notes',
        'invoice_data',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_run_date' => 'date',
            'last_run_date' => 'date',
            'interval' => 'integer',
            'occurrences' => 'integer',
            'day_of_month' => 'integer',
            'generated_count' => 'integer',
            'is_active' => 'boolean',
            'invoice_data' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Account::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

