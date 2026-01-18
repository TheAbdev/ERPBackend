<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends ErpBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'erp_workflow_actions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'workflow_instance_id',
        'step_order',
        'user_id',
        'action',
        'comment',
    ];

    /**
     * Get the tenant that owns the action.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the workflow instance.
     *
     * @return BelongsTo
     */
    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Check if action is approval.
     *
     * @return bool
     */
    public function isApproval(): bool
    {
        return $this->action === 'approve';
    }

    /**
     * Check if action is rejection.
     *
     * @return bool
     */
    public function isRejection(): bool
    {
        return $this->action === 'reject';
    }
}

