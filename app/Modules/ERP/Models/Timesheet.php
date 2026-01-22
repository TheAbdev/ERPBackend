<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timesheet extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, ModelChangeTracker;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'project_id',
        'project_task_id',
        'date',
        'hours',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'submitted';
    }
}















