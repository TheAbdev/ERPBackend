<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recruitment extends HrBaseModel
{
    protected $table = 'hr_recruitments';

    protected $fillable = [
        'tenant_id',
        'position_id',
        'candidate_name',
        'email',
        'phone',
        'status',
        'applied_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
}

