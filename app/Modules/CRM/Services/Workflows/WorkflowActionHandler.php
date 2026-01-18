<?php

namespace App\Modules\CRM\Services\Workflows;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Activity;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Lead;
use App\Notifications\ActivityDueNotification;

class WorkflowActionHandler
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Execute an action.
     *
     * @param  array  $action
     * @param  mixed  $triggerData
     * @return array Result of action execution
     */
    public function execute(array $action, $triggerData): array
    {
        $type = $action['type'] ?? null;

        if (! $type) {
            return ['success' => false, 'error' => 'Action type not specified'];
        }

        return match ($type) {
            'create_activity' => $this->createActivity($action, $triggerData),
            'update_deal_status' => $this->updateDealStatus($action, $triggerData),
            'assign_user' => $this->assignUser($action, $triggerData),
            'send_notification' => $this->sendNotification($action, $triggerData),
            default => ['success' => false, 'error' => "Unknown action type: {$type}"],
        };
    }

    /**
     * Create an activity.
     *
     * @param  array  $action
     * @param  mixed  $triggerData
     * @return array
     */
    protected function createActivity(array $action, $triggerData): array
    {
        try {
            $entity = $triggerData['entity'] ?? null;
            if (! $entity) {
                return ['success' => false, 'error' => 'No entity in trigger data'];
            }

            $activityData = [
                'tenant_id' => $this->tenantContext->getTenantId(),
                'type' => $action['activity_type'] ?? 'task',
                'subject' => $this->resolveTemplate($action['subject'] ?? 'Activity', $triggerData),
                'description' => $this->resolveTemplate($action['description'] ?? '', $triggerData),
                'due_date' => isset($action['due_date'])
                    ? \Carbon\Carbon::parse($this->resolveTemplate($action['due_date'], $triggerData))
                    : null,
                'priority' => $action['priority'] ?? 'medium',
                'status' => 'pending',
                'related_type' => get_class($entity),
                'related_id' => $entity->id,
                'assigned_to' => $action['assigned_to'] ?? $entity->assigned_to ?? null,
                'created_by' => $action['created_by'] ?? $entity->created_by ?? null,
            ];

            $activity = Activity::create($activityData);

            return [
                'success' => true,
                'action_type' => 'create_activity',
                'activity_id' => $activity->id,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update deal status.
     *
     * @param  array  $action
     * @param  mixed  $triggerData
     * @return array
     */
    protected function updateDealStatus(array $action, $triggerData): array
    {
        try {
            $entity = $triggerData['entity'] ?? null;
            if (! $entity || ! $entity instanceof Deal) {
                return ['success' => false, 'error' => 'Entity is not a Deal'];
            }

            $status = $action['status'] ?? null;
            if (! $status) {
                return ['success' => false, 'error' => 'Status not specified'];
            }

            $entity->update(['status' => $status]);

            return [
                'success' => true,
                'action_type' => 'update_deal_status',
                'deal_id' => $entity->id,
                'new_status' => $status,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Assign user to entity.
     *
     * @param  array  $action
     * @param  mixed  $triggerData
     * @return array
     */
    protected function assignUser(array $action, $triggerData): array
    {
        try {
            $entity = $triggerData['entity'] ?? null;
            if (! $entity) {
                return ['success' => false, 'error' => 'No entity in trigger data'];
            }

            $userId = $action['user_id'] ?? null;
            if (! $userId) {
                return ['success' => false, 'error' => 'User ID not specified'];
            }

            // Verify user belongs to same tenant
            $user = \App\Models\User::where('id', $userId)
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->first();

            if (! $user) {
                return ['success' => false, 'error' => 'User not found or not in same tenant'];
            }

            if (isset($entity->assigned_to)) {
                $entity->update(['assigned_to' => $userId]);
            } else {
                return ['success' => false, 'error' => 'Entity does not support assignment'];
            }

            return [
                'success' => true,
                'action_type' => 'assign_user',
                'entity_id' => $entity->id,
                'user_id' => $userId,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send notification.
     *
     * @param  array  $action
     * @param  mixed  $triggerData
     * @return array
     */
    protected function sendNotification(array $action, $triggerData): array
    {
        try {
            $entity = $triggerData['entity'] ?? null;
            if (! $entity) {
                return ['success' => false, 'error' => 'No entity in trigger data'];
            }

            $userId = $action['user_id'] ?? null;
            if (! $userId) {
                // Try to get from entity
                $userId = $entity->assigned_to ?? $entity->created_by ?? null;
            }

            if (! $userId) {
                return ['success' => false, 'error' => 'No user to notify'];
            }

            $user = \App\Models\User::where('id', $userId)
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->first();

            if (! $user) {
                return ['success' => false, 'error' => 'User not found'];
            }

            // Create a generic notification based on entity type
            $notification = $this->createNotificationForEntity($entity, $action);
            if ($notification) {
                $user->notify($notification);
            }

            return [
                'success' => true,
                'action_type' => 'send_notification',
                'user_id' => $userId,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Resolve template variables in strings.
     *
     * @param  string  $template
     * @param  mixed  $triggerData
     * @return string
     */
    protected function resolveTemplate(string $template, $triggerData): string
    {
        $entity = $triggerData['entity'] ?? null;

        if (! $entity) {
            return $template;
        }

        // Replace {{field}} with entity field values
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($entity) {
            $field = $matches[1];
            return $entity->$field ?? $matches[0];
        }, $template);
    }

    /**
     * Create appropriate notification for entity type.
     *
     * @param  mixed  $entity
     * @param  array  $action
     * @return \Illuminate\Notifications\Notification|null
     */
    protected function createNotificationForEntity($entity, array $action)
    {
        $entityClass = get_class($entity);

        if (str_contains($entityClass, 'Activity')) {
            return new \App\Notifications\ActivityDueNotification($entity);
        }

        if (str_contains($entityClass, 'Deal')) {
            return new \App\Notifications\DealStatusNotification($entity, $action['status'] ?? 'updated');
        }

        // For other entities, create a generic notification
        // This could be extended with a generic workflow notification class
        return null;
    }
}

