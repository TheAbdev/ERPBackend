<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends HrBaseModel
{
    protected $table = 'hr_contracts';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'start_date',
        'end_date',
        'type',
        'salary',
        'status',
        'notes',
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
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

