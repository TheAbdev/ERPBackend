<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Core\Services\AuditService;
use App\Modules\ERP\Models\Workflow;
use App\Modules\ERP\Models\WorkflowInstance;
use App\Modules\ERP\Models\WorkflowAction;
use App\Modules\ERP\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing workflow approvals.
 */
class WorkflowService extends BaseService
{
    protected AuditService $auditService;

    public function __construct(
        TenantContext $tenantContext,
        AuditService $auditService
    ) {
        parent::__construct($tenantContext);
        $this->auditService = $auditService;
    }

    /**
     * Start a workflow for an entity.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\WorkflowInstance
     *
     * @throws \Exception
     */
    public function startWorkflow(Model $entity, int $userId): WorkflowInstance
    {
        $entityType = get_class($entity);

        // Find active workflow for this entity type
        $workflow = Workflow::where('tenant_id', $this->getTenantId())
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            throw new \Exception("No active workflow found for entity type: {$entityType}");
        }

        // Check if workflow instance already exists
        $existingInstance = WorkflowInstance::where('tenant_id', $this->getTenantId())
            ->where('entity_type', $entityType)
            ->where('entity_id', $entity->id)
            ->where('status', 'pending')
            ->first();

        if ($existingInstance) {
            throw new \Exception('Workflow already in progress for this entity.');
        }

        return DB::transaction(function () use ($workflow, $entity, $entityType, $userId) {
            // Create workflow instance
            $instance = WorkflowInstance::create([
                'tenant_id' => $this->getTenantId(),
                'workflow_id' => $workflow->id,
                'entity_type' => $entityType,
                'entity_id' => $entity->id,
                'current_step' => 1,
                'status' => 'pending',
                'initiated_by' => $userId,
                'initiated_at' => now(),
            ]);

            // Get first step
            $firstStep = $workflow->getFirstStep();

            if (!$firstStep) {
                throw new \Exception('Workflow has no steps defined.');
            }

            // Auto-approve first step if configured
            if ($firstStep->auto_approve) {
                $this->approveStep($instance, $firstStep, $userId, 'Auto-approved');
            }

            // Log audit
            $this->auditService->log(
                'create',
                $instance,
                null,
                [
                    'workflow_id' => $workflow->id,
                    'workflow_name' => $workflow->name,
                    'entity_type' => $entityType,
                    'entity_id' => $entity->id,
                ],
                [
                    'description' => "Started workflow: {$workflow->name} for {$entityType}",
                ]
            );

            return $instance;
        });
    }

    /**
     * Approve a workflow step.
     *
     * @param  \App\Modules\ERP\Models\WorkflowInstance  $instance
     * @param  \App\Modules\ERP\Models\WorkflowStep  $step
     * @param  int  $userId
     * @param  string|null  $comment
     * @return \App\Modules\ERP\Models\WorkflowInstance
     *
     * @throws \Exception
     */
    public function approveStep(
        WorkflowInstance $instance,
        WorkflowStep $step,
        int $userId,
        ?string $comment = null
    ): WorkflowInstance {
        if (!$instance->isPending()) {
            throw new \Exception('Workflow is not pending approval.');
        }

        if ($instance->current_step !== $step->step_order) {
            throw new \Exception('Cannot approve this step. Current step mismatch.');
        }

        $user = \App\Models\User::findOrFail($userId);

        if (!$step->canApprove($user)) {
            throw new \Exception('User does not have permission to approve this step.');
        }

        return DB::transaction(function () use ($instance, $step, $userId, $comment) {
            // Create workflow action
            WorkflowAction::create([
                'tenant_id' => $this->getTenantId(),
                'workflow_instance_id' => $instance->id,
                'step_order' => $step->step_order,
                'user_id' => $userId,
                'action' => 'approve',
                'comment' => $comment,
            ]);

            // Check if there's a next step
            $nextStep = $instance->getNextStep();

            if ($nextStep) {
                // Move to next step
                $instance->update([
                    'current_step' => $nextStep->step_order,
                ]);

                // Auto-approve next step if configured
                if ($nextStep->auto_approve) {
                    return $this->approveStep($instance, $nextStep, $userId, 'Auto-approved');
                }
            } else {
                // All steps approved - complete workflow
                $instance->update([
                    'status' => 'approved',
                    'completed_at' => now(),
                ]);

                // Mark entity as approved/postable
                $this->markEntityAsApproved($instance->entity);
            }

            // Log audit
            $this->auditService->log(
                'approve',
                $instance,
                ['current_step' => $step->step_order - 1],
                ['current_step' => $instance->current_step, 'status' => $instance->status],
                [
                    'description' => "Approved workflow step {$step->step_order}",
                    'workflow_instance_id' => $instance->id,
                    'comment' => $comment,
                ]
            );

            return $instance->fresh();
        });
    }

    /**
     * Reject a workflow step.
     *
     * @param  \App\Modules\ERP\Models\WorkflowInstance  $instance
     * @param  \App\Modules\ERP\Models\WorkflowStep  $step
     * @param  int  $userId
     * @param  string|null  $comment
     * @return \App\Modules\ERP\Models\WorkflowInstance
     *
     * @throws \Exception
     */
    public function rejectStep(
        WorkflowInstance $instance,
        WorkflowStep $step,
        int $userId,
        ?string $comment = null
    ): WorkflowInstance {
        if (!$instance->isPending()) {
            throw new \Exception('Workflow is not pending approval.');
        }

        if ($instance->current_step !== $step->step_order) {
            throw new \Exception('Cannot reject this step. Current step mismatch.');
        }

        $user = \App\Models\User::findOrFail($userId);

        if (!$step->canApprove($user)) {
            throw new \Exception('User does not have permission to reject this step.');
        }

        return DB::transaction(function () use ($instance, $step, $userId, $comment) {
            // Create workflow action
            WorkflowAction::create([
                'tenant_id' => $this->getTenantId(),
                'workflow_instance_id' => $instance->id,
                'step_order' => $step->step_order,
                'user_id' => $userId,
                'action' => 'reject',
                'comment' => $comment,
            ]);

            // Reject workflow
            $instance->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            // Roll back entity to draft
            $this->rollbackEntityToDraft($instance->entity);

            // Log audit
            $this->auditService->log(
                'reject',
                $instance,
                ['status' => 'pending'],
                ['status' => 'rejected'],
                [
                    'description' => "Rejected workflow step {$step->step_order}",
                    'workflow_instance_id' => $instance->id,
                    'comment' => $comment,
                ]
            );

            return $instance->fresh();
        });
    }

    /**
     * Advance workflow to next step.
     *
     * @param  \App\Modules\ERP\Models\WorkflowInstance  $instance
     * @return \App\Modules\ERP\Models\WorkflowInstance
     *
     * @throws \Exception
     */
    public function advanceWorkflow(WorkflowInstance $instance): WorkflowInstance
    {
        if (!$instance->isPending()) {
            throw new \Exception('Workflow is not pending.');
        }

        $nextStep = $instance->getNextStep();

        if (!$nextStep) {
            throw new \Exception('No next step available.');
        }

        $instance->update([
            'current_step' => $nextStep->step_order,
        ]);

        // Auto-approve if configured
        if ($nextStep->auto_approve) {
            return $this->approveStep($instance, $nextStep, $instance->initiated_by, 'Auto-approved');
        }

        return $instance->fresh();
    }

    /**
     * Mark entity as approved/postable.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return void
     */
    protected function markEntityAsApproved(Model $entity): void
    {
        // Add approved flag or status based on entity type
        if (method_exists($entity, 'markAsApproved')) {
            $entity->markAsApproved();
        } elseif (isset($entity->status)) {
            // For invoices, change from pending_approval to draft so they can be issued
            if (in_array($entity->status, ['pending_approval'])) {
                $entity->update(['status' => 'draft']);
            } else {
                $entity->update(['status' => 'approved']);
            }
        }
    }

    /**
     * Roll back entity to draft.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return void
     */
    protected function rollbackEntityToDraft(Model $entity): void
    {
        // Roll back to draft based on entity type
        if (method_exists($entity, 'rollbackToDraft')) {
            $entity->rollbackToDraft();
        } elseif (isset($entity->status)) {
            $entity->update(['status' => 'draft']);
        }
    }

    /**
     * Get workflow for entity type.
     *
     * @param  string  $entityType
     * @return \App\Modules\ERP\Models\Workflow|null
     */
    public function getWorkflowForEntityType(string $entityType): ?Workflow
    {
        return Workflow::where('tenant_id', $this->getTenantId())
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Check if entity requires approval.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return bool
     */
    public function requiresApproval(Model $entity): bool
    {
        $entityType = get_class($entity);
        return $this->getWorkflowForEntityType($entityType) !== null;
    }
}

