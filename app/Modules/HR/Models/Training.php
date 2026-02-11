<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Training extends HrBaseModel
{
    protected $table = 'hr_trainings';

    protected $fillable = [
        'tenant_id',
        'title',
        'provider',
        'start_date',
        'end_date',
        'cost',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class, 'training_id');
    }
}

