<?php

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentOpening extends HrBaseModel
{
    protected $table = 'hr_recruitment_openings';

    protected $fillable = [
        'tenant_id',
        'department_id',
        'position_id',
        'title',
        'description',
        'openings_count',
        'status',
        'posted_date',
        'close_date',
    ];

    protected function casts(): array
    {
        return [
            'posted_date' => 'date',
            'close_date' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}

