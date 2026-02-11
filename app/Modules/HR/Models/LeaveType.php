<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends HrBaseModel
{
    protected $table = 'hr_leave_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_paid',
        'max_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'max_days' => 'integer',
        ];
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
}

