<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends HrBaseModel
{
    protected $table = 'hr_attendance_records';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'attendance_date',
        'check_in',
        'check_out',
        'hours_worked',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'hours_worked' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

