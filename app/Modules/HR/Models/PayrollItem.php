<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends HrBaseModel
{
    protected $table = 'hr_payroll_items';

    protected $fillable = [
        'tenant_id',
        'payroll_run_id',
        'employee_id',
        'gross',
        'deductions',
        'net',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net' => 'decimal:2',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

