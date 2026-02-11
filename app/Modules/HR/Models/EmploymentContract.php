<?php

namespace App\Modules\HR\Models;

use App\Modules\ERP\Models\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentContract extends HrBaseModel
{
    protected $table = 'hr_employment_contracts';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'currency_id',
        'contract_type',
        'start_date',
        'end_date',
        'salary',
        'status',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'salary' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

