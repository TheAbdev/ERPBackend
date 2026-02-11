<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAssignment extends HrBaseModel
{
    protected $table = 'hr_training_assignments';

    protected $fillable = [
        'tenant_id',
        'training_id',
        'employee_id',
        'status',
        'completion_date',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
        ];
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'training_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

