<?php

namespace App\Modules\ERP\Listeners;

use App\Events\EntityUpdated;
use App\Modules\ERP\Services\ActivityFeedService;
use App\Modules\ERP\Services\WebhookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class HandleEntityUpdated
{
    protected ActivityFeedService $activityFeedService;
    protected WebhookService $webhookService;

    public function __construct(
        ActivityFeedService $activityFeedService,
        WebhookService $webhookService
    ) {
        $this->activityFeedService = $activityFeedService;
        $this->webhookService = $webhookService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EntityUpdated  $event
     * @return void
     */
    public function handle(EntityUpdated $event): void
    {
        if (!($event->entity instanceof Model)) {
            return;
        }

        $entity = $event->entity;
        $module = $this->getModule($entity);

        try {
            // Log to activity feed
            $this->activityFeedService->logAction($entity, 'updated', $event->userId ?? null);

            // Trigger webhook
            $this->webhookService->triggerEvent($module, "{$module}.entity.updated", $entity);
        } catch (\Exception $e) {
            Log::error('Failed to handle entity updated event', [
                'entity' => get_class($entity),
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get module name from entity.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return string
     */
    protected function getModule(Model $entity): string
    {
        $class = get_class($entity);

        if (str_contains($class, 'Modules\\ERP\\')) {
            return 'ERP';
        } elseif (str_contains($class, 'Modules\\CRM\\')) {
            return 'CRM';
        }

        return 'Core';
    }
}


















