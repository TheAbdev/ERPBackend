<?php

namespace App\Modules\CRM\Services\Workflows;

use App\Core\Services\TenantContext;
use App\Jobs\ExecuteWorkflowJob;
use App\Modules\CRM\Models\Workflow;
use Illuminate\Support\Facades\Log;

class WorkflowEngineService
{
    protected TenantContext $tenantContext;
    protected WorkflowConditionEvaluator $conditionEvaluator;
    protected WorkflowActionHandler $actionHandler;

    public function __construct(
        TenantContext $tenantContext,
        WorkflowConditionEvaluator $conditionEvaluator,
        WorkflowActionHandler $actionHandler
    ) {
        $this->tenantContext = $tenantContext;
        $this->conditionEvaluator = $conditionEvaluator;
        $this->actionHandler = $actionHandler;
    }

    /**
     * Trigger workflows for a given event.
     *
     * @param  string  $event
     * @param  mixed  $entity
     * @param  array  $additionalData
     * @return void
     */
    public function trigger(string $event, $entity, array $additionalData = []): void
    {
        $tenantId = $this->tenantContext->getTenantId();

        if (! $tenantId) {
            Log::warning('WorkflowEngine: No tenant context available');
            return;
        }

        // Get active workflows for this event
        $workflows = Workflow::where('tenant_id', $tenantId)
            ->where('event', $event)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        if ($workflows->isEmpty()) {
            return;
        }

        $triggerData = array_merge([
            'entity' => $entity,
        ], $additionalData);

        foreach ($workflows as $workflow) {
            // Dispatch job for queue-based execution
            ExecuteWorkflowJob::dispatch($workflow->id, $event, $entity->id, get_class($entity), $triggerData);
        }
    }

    /**
     * Execute a workflow.
     *
     * @param  \App\Modules\CRM\Models\Workflow  $workflow
     * @param  mixed  $triggerData
     * @return array
     */
    public function execute(Workflow $workflow, array $triggerData): array
    {
        $run = $workflow->runs()->create([
            'tenant_id' => $this->tenantContext->getTenantId(),
            'trigger_type' => $this->getTriggerType($triggerData),
            'trigger_id' => $triggerData['entity']->id ?? null,
            'status' => 'running',
            'executed_at' => now(),
        ]);

        $executionLog = [];
        $hasErrors = false;

        try {
            // Evaluate conditions
            $conditions = $workflow->conditions ?? [];
            if (! $this->conditionEvaluator->evaluate($conditions, $triggerData)) {
                $run->update([
                    'status' => 'completed',
                    'execution_log' => [['message' => 'Conditions not met, workflow skipped']],
                ]);
                return ['success' => false, 'skipped' => true, 'reason' => 'Conditions not met'];
            }

            // Execute actions
            $actions = $workflow->actions ?? [];
            foreach ($actions as $action) {
                $result = $this->actionHandler->execute($action, $triggerData);
                $executionLog[] = $result;

                if (! $result['success']) {
                    $hasErrors = true;
                }
            }

            $run->update([
                'status' => $hasErrors ? 'failed' : 'completed',
                'execution_log' => $executionLog,
            ]);

            return [
                'success' => ! $hasErrors,
                'workflow_id' => $workflow->id,
                'run_id' => $run->id,
                'execution_log' => $executionLog,
            ];
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'execution_log' => $executionLog,
            ]);

            Log::error('Workflow execution failed', [
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'workflow_id' => $workflow->id,
                'run_id' => $run->id,
            ];
        }
    }

    /**
     * Get trigger type from entity class.
     *
     * @param  array  $triggerData
     * @return string
     */
    protected function getTriggerType(array $triggerData): string
    {
        $entity = $triggerData['entity'] ?? null;

        if (! $entity) {
            return 'unknown';
        }

        $className = get_class($entity);

        return match (true) {
            str_contains($className, 'Lead') => 'lead',
            str_contains($className, 'Deal') => 'deal',
            str_contains($className, 'Activity') => 'activity',
            default => 'unknown',
        };
    }
}

