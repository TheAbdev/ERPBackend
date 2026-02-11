<?php

namespace App\Modules\HR\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends HrBaseModel
{
    protected $table = 'hr_performance_reviews';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'reviewer_id',
        'period_start',
        'period_end',
        'score',
        'status',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'score' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
