<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\ModelChangeTracker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectBudget extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, ModelChangeTracker;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'category',
        'description',
        'budgeted_amount',
        'actual_amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'budgeted_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getVarianceAttribute(): float
    {
        return $this->actual_amount - $this->budgeted_amount;
    }

    public function getVariancePercentageAttribute(): float
    {
        if ($this->budgeted_amount == 0) {
            return 0;
        }

        return ($this->variance / $this->budgeted_amount) * 100;
    }
}















