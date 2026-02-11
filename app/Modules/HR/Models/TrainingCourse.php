<?php

namespace App\Modules\HR\Models;

use App\Modules\ERP\Models\Currency;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCourse extends HrBaseModel
{
    protected $table = 'hr_training_courses';

    protected $fillable = [
        'tenant_id',
        'currency_id',
        'title',
        'description',
        'provider',
        'cost',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class, 'training_course_id');
    }
}

