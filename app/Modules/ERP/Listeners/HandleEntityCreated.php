<?php

namespace App\Modules\ERP\Listeners;

use App\Events\EntityCreated;
use App\Modules\ERP\Services\ActivityFeedService;
use App\Modules\ERP\Services\NotificationService;
use App\Modules\ERP\Services\WebhookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class HandleEntityCreated
{
    protected ActivityFeedService $activityFeedService;
    protected NotificationService $notificationService;
    protected WebhookService $webhookService;

    public function __construct(
        ActivityFeedService $activityFeedService,
        NotificationService $notificationService,
        WebhookService $webhookService
    ) {
        $this->activityFeedService = $activityFeedService;
        $this->notificationService = $notificationService;
        $this->webhookService = $webhookService;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\EntityCreated  $event
     * @return void
     */
    public function handle(EntityCreated $event): void
    {
        if (!($event->entity instanceof Model)) {
            return;
        }

        $entity = $event->entity;
        $module = $this->getModule($entity);

        try {
            // Log to activity feed
            $this->activityFeedService->logAction($entity, 'created', $event->userId ?? null);

            // Trigger webhook
            $this->webhookService->triggerEvent($module, "{$module}.entity.created", $entity);

            // Send notification if needed
            if (isset($event->notifyUsers) && is_array($event->notifyUsers)) {
                $this->notificationService->sendToUsers(
                    $event->notifyUsers,
                    "New {$this->getEntityName($entity)} Created",
                    "A new {$this->getEntityName($entity)} has been created.",
                    'info',
                    get_class($entity),
                    $entity->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle entity created event', [
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

    /**
     * Get entity name for display.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return string
     */
    protected function getEntityName(Model $entity): string
    {
        return class_basename($entity);
    }
}







