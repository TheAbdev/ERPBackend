<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends ErpBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'erp_workflow_steps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'workflow_id',
        'step_order',
        'role_id',
        'permission',
        'action',
        'auto_approve',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auto_approve' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the step.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the workflow.
     *
     * @return BelongsTo
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the role.
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Role::class);
    }

    /**
     * Get the workflow actions for this step.
     *
     * @return HasMany
     */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class, 'workflow_instance_id')
            ->where('step_order', $this->step_order);
    }

    /**
     * Check if step requires approval.
     *
     * @return bool
     */
    public function requiresApproval(): bool
    {
        return !$this->auto_approve;
    }

    /**
     * Check if user can approve this step.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function canApprove(\App\Models\User $user): bool
    {
        if ($this->auto_approve) {
            return true;
        }

        if ($this->role_id && $user->hasRole($this->role_id)) {
            return true;
        }

        if ($this->permission && $user->can($this->permission)) {
            return true;
        }

        return false;
    }
}

