<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends HrBaseModel
{
    protected $table = 'hr_employee_documents';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'name',
        'type',
        'file_path',
        'issued_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
