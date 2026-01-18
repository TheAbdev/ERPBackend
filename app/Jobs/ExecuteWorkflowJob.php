<?php

namespace App\Jobs;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Workflow;
use App\Modules\CRM\Services\Workflows\WorkflowEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExecuteWorkflowJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $workflowId,
        public string $event,
        public int $entityId,
        public string $entityClass,
        public array $triggerData
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(WorkflowEngineService $engine, TenantContext $tenantContext): void
    {
        try {
            $workflow = Workflow::find($this->workflowId);

            if (! $workflow || ! $workflow->is_active) {
                return;
            }

            // Set tenant context
            $tenantContext->setTenant($workflow->tenant);

            // Load entity
            if (! class_exists($this->entityClass)) {
                Log::warning('Workflow: Entity class not found', [
                    'workflow_id' => $this->workflowId,
                    'entity_class' => $this->entityClass,
                ]);
                return;
            }

            $entity = call_user_func([$this->entityClass, 'find'], $this->entityId);
            if (! $entity) {
                Log::warning('Workflow: Entity not found', [
                    'workflow_id' => $this->workflowId,
                    'entity_id' => $this->entityId,
                    'entity_class' => $this->entityClass,
                ]);
                return;
            }

            // Update trigger data with loaded entity
            $this->triggerData['entity'] = $entity;

            // Execute workflow
            $engine->execute($workflow, $this->triggerData);
        } catch (\Exception $e) {
            Log::error('Workflow job failed', [
                'workflow_id' => $this->workflowId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
