<?php

namespace App\Modules\ERP\Listeners;

use App\Modules\ERP\Services\ActivityFeedService;
use App\Modules\ERP\Services\NotificationService;
use App\Modules\ERP\Services\WebhookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Generic listener for handling entity events.
 */
class HandleEntityEvent
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
     * Handle entity created event.
     *
     * @param  object  $event
     * @return void
     */
    public function handleCreated($event): void
    {
        if (!isset($event->entity) || !($event->entity instanceof Model)) {
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
     * Handle entity updated event.
     *
     * @param  object  $event
     * @return void
     */
    public function handleUpdated($event): void
    {
        if (!isset($event->entity) || !($event->entity instanceof Model)) {
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
     * Handle entity approved event.
     *
     * @param  object  $event
     * @return void
     */
    public function handleApproved($event): void
    {
        if (!isset($event->entity) || !($event->entity instanceof Model)) {
            return;
        }

        $entity = $event->entity;
        $module = $this->getModule($entity);

        try {
            // Log to activity feed
            $this->activityFeedService->logAction($entity, 'approved', $event->userId ?? null);

            // Trigger webhook
            $this->webhookService->triggerEvent($module, "{$module}.entity.approved", $entity);

            // Send notification
            if (isset($entity->created_by)) {
                $this->notificationService->sendToUser(
                    $entity->created_by,
                    "{$this->getEntityName($entity)} Approved",
                    "Your {$this->getEntityName($entity)} has been approved.",
                    'info',
                    get_class($entity),
                    $entity->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle entity approved event', [
                'entity' => get_class($entity),
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle entity rejected event.
     *
     * @param  object  $event
     * @return void
     */
    public function handleRejected($event): void
    {
        if (!isset($event->entity) || !($event->entity instanceof Model)) {
            return;
        }

        $entity = $event->entity;
        $module = $this->getModule($entity);

        try {
            // Log to activity feed
            $this->activityFeedService->logAction($entity, 'rejected', $event->userId ?? null, [
                'reason' => $event->reason ?? null,
            ]);

            // Trigger webhook
            $this->webhookService->triggerEvent($module, "{$module}.entity.rejected", $entity);

            // Send notification
            if (isset($entity->created_by)) {
                $this->notificationService->sendToUser(
                    $entity->created_by,
                    "{$this->getEntityName($entity)} Rejected",
                    "Your {$this->getEntityName($entity)} has been rejected." . ($event->reason ? " Reason: {$event->reason}" : ''),
                    'warning',
                    get_class($entity),
                    $entity->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle entity rejected event', [
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

