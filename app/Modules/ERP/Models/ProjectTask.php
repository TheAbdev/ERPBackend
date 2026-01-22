<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use App\Modules\CRM\Models\Activity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, ModelChangeTracker;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'activity_id',
        'name',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'assigned_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'priority' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}















