<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\CalendarConnection;
use App\Modules\CRM\Models\Activity;
use Illuminate\Support\Facades\Log;

class CalendarSyncService
{
    protected TenantContext $tenantContext;
    protected GoogleCalendarService $googleService;
    protected OutlookCalendarService $outlookService;

    public function __construct(
        TenantContext $tenantContext,
        GoogleCalendarService $googleService,
        OutlookCalendarService $outlookService
    ) {
        $this->tenantContext = $tenantContext;
        $this->googleService = $googleService;
        $this->outlookService = $outlookService;
    }

    /**
     * Sync activity to all connected calendars.
     */
    public function syncActivityToCalendars(Activity $activity): array
    {
        $results = [];

        $connections = CalendarConnection::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('user_id', $activity->created_by)
            ->where('is_active', true)
            ->where('sync_enabled', true)
            ->get();

        foreach ($connections as $connection) {
            try {
                $eventId = null;

                if ($connection->provider === 'google') {
                    $eventId = $this->googleService->syncActivityToCalendar($activity, $connection);
                } elseif ($connection->provider === 'outlook') {
                    $eventId = $this->outlookService->syncActivityToCalendar($activity, $connection);
                }

                if ($eventId) {
                    $results[$connection->provider] = $eventId;
                    
                    // Update activity metadata
                    $metadata = $activity->metadata ?? [];
                    $metadata[$connection->provider . '_event_id'] = $eventId;
                    $activity->update(['metadata' => $metadata]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync activity to {$connection->provider}: " . $e->getMessage());
                $results[$connection->provider] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Sync from all connected calendars.
     */
    public function syncFromAllCalendars(int $userId): array
    {
        $results = [];

        $connections = CalendarConnection::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('sync_enabled', true)
            ->get();

        foreach ($connections as $connection) {
            try {
                $syncedCount = 0;

                if ($connection->provider === 'google') {
                    $syncedCount = $this->googleService->syncFromCalendar($connection);
                } elseif ($connection->provider === 'outlook') {
                    $syncedCount = $this->outlookService->syncFromCalendar($connection);
                }

                $results[$connection->provider] = [
                    'synced_count' => $syncedCount,
                    'connection_id' => $connection->id,
                ];
            } catch (\Exception $e) {
                Log::error("Failed to sync from {$connection->provider}: " . $e->getMessage());
                $results[$connection->provider] = [
                    'error' => $e->getMessage(),
                    'connection_id' => $connection->id,
                ];
            }
        }

        return $results;
    }

    /**
     * Update activity in calendars when activity is updated.
     */
    public function updateActivityInCalendars(Activity $activity): array
    {
        $results = [];

        $metadata = $activity->metadata ?? [];
        $connections = CalendarConnection::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('user_id', $activity->created_by)
            ->where('is_active', true)
            ->where('sync_enabled', true)
            ->get();

        foreach ($connections as $connection) {
            $eventIdKey = $connection->provider . '_event_id';
            
            if (!isset($metadata[$eventIdKey])) {
                // Event doesn't exist, create it
                $this->syncActivityToCalendars($activity);
                continue;
            }

            try {
                // Update existing event (would require additional methods in services)
                // For now, we'll just log that update is needed
                Log::info("Activity updated, calendar sync needed for {$connection->provider} event {$metadata[$eventIdKey]}");
                $results[$connection->provider] = 'update_required';
            } catch (\Exception $e) {
                Log::error("Failed to update activity in {$connection->provider}: " . $e->getMessage());
                $results[$connection->provider] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Delete activity from calendars.
     */
    public function deleteActivityFromCalendars(Activity $activity): array
    {
        $results = [];

        $metadata = $activity->metadata ?? [];
        $connections = CalendarConnection::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('user_id', $activity->created_by)
            ->where('is_active', true)
            ->get();

        foreach ($connections as $connection) {
            $eventIdKey = $connection->provider . '_event_id';
            
            if (!isset($metadata[$eventIdKey])) {
                continue;
            }

            try {
                // Delete event (would require additional methods in services)
                Log::info("Activity deleted, calendar sync needed for {$connection->provider} event {$metadata[$eventIdKey]}");
                $results[$connection->provider] = 'delete_required';
            } catch (\Exception $e) {
                Log::error("Failed to delete activity from {$connection->provider}: " . $e->getMessage());
                $results[$connection->provider] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }
}















