<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\CalendarConnection;
use App\Modules\CRM\Models\Activity;
use Microsoft\Graph\Graph;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\Models\Event;
use Illuminate\Support\Facades\Log;

class OutlookCalendarService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get authorization URL for Outlook Calendar.
     */
    public function getAuthorizationUrl(int $userId, ?int $tenantId = null): string
    {
        $clientId = config('services.microsoft.client_id');
        $redirectUri = config('services.microsoft.redirect_uri');
        
        if (!$clientId) {
            throw new \Exception('Microsoft Calendar credentials are not configured. Please set MICROSOFT_CLIENT_ID in .env file.');
        }
        
        $scopes = 'https://graph.microsoft.com/Calendars.ReadWrite offline_access';
        
        // Get tenant_id from parameter, tenant context, or authenticated user
        if (!$tenantId) {
            $tenantId = $this->tenantContext->getTenantId();
        }
        if (!$tenantId) {
            $user = auth()->user();
            $tenantId = $user?->tenant_id;
        }
        
        $state = json_encode([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);

        $params = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => $scopes,
            'state' => $state,
        ]);

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . $params;
    }

    /**
     * Handle OAuth callback and create connection.
     */
    public function handleCallback(string $code, int $userId, ?int $tenantId = null): CalendarConnection
    {
        $clientId = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');
        $redirectUri = config('services.microsoft.redirect_uri');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Microsoft Calendar credentials are not configured. Please set MICROSOFT_CLIENT_ID and MICROSOFT_CLIENT_SECRET in .env file.');
        }

        // Exchange code for token
        $tokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $tokenData = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $response = \Illuminate\Support\Facades\Http::asForm()->post($tokenUrl, $tokenData);
        $tokenResponse = $response->json();

        if (!$response->successful() || isset($tokenResponse['error'])) {
            throw new \Exception('Failed to get access token: ' . ($tokenResponse['error_description'] ?? 'Unknown error'));
        }

        // Get tenant_id from parameter, tenant context, or authenticated user
        if (!$tenantId) {
            $tenantId = $this->tenantContext->getTenantId();
        }
        if (!$tenantId) {
            $user = auth()->user();
            $tenantId = $user?->tenant_id;
        }

        if (!$tenantId) {
            throw new \Exception('Tenant ID is required to create calendar connection.');
        }

        return CalendarConnection::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'provider' => 'outlook',
            'access_token' => $tokenResponse['access_token'],
            'refresh_token' => $tokenResponse['refresh_token'] ?? null,
            'token_expires_at' => isset($tokenResponse['expires_in']) 
                ? now()->addSeconds($tokenResponse['expires_in']) 
                : null,
            'is_active' => true,
            'sync_enabled' => true,
        ]);
    }

    /**
     * Get Graph client.
     */
    protected function getGraphClient(CalendarConnection $connection): Graph
    {
        $graph = new Graph();
        
        if ($connection->isTokenExpired() && $connection->refresh_token) {
            $this->refreshToken($connection);
        }

        $graph->setAccessToken($connection->access_token);

        return $graph;
    }

    /**
     * Refresh access token.
     */
    protected function refreshToken(CalendarConnection $connection): void
    {
        $clientId = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');
        $redirectUri = config('services.microsoft.redirect_uri');

        $tokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $tokenData = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $connection->refresh_token,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'refresh_token',
        ];

        $response = \Illuminate\Support\Facades\Http::asForm()->post($tokenUrl, $tokenData);
        $tokenResponse = $response->json();

        if ($response->successful() && !isset($tokenResponse['error'])) {
            $connection->update([
                'access_token' => $tokenResponse['access_token'],
                'refresh_token' => $tokenResponse['refresh_token'] ?? $connection->refresh_token,
                'token_expires_at' => isset($tokenResponse['expires_in']) 
                    ? now()->addSeconds($tokenResponse['expires_in']) 
                    : null,
            ]);
        }
    }

    /**
     * Sync activities to Outlook Calendar.
     */
    public function syncActivityToCalendar(Activity $activity, CalendarConnection $connection): ?string
    {
        try {
            $graph = $this->getGraphClient($connection);

            $event = new Event();
            $event->setSubject($activity->subject);
            $event->setBody([
                'contentType' => 'HTML',
                'content' => $activity->description ?? '',
            ]);

            if ($activity->due_date) {
                $start = new \Microsoft\Graph\Models\DateTimeTimeZone();
                $start->setDateTime($activity->due_date->toIso8601String());
                $start->setTimeZone('UTC');
                $event->setStart($start);

                $end = new \Microsoft\Graph\Models\DateTimeTimeZone();
                $end->setDateTime($activity->due_date->addHours(1)->toIso8601String());
                $end->setTimeZone('UTC');
                $event->setEnd($end);
            }

            $calendarId = $connection->calendar_id ?? 'calendar';
            $requestBody = $event;
            
            $result = $graph->createRequest('POST', "/me/calendars/{$calendarId}/events")
                ->attachBody($requestBody)
                ->execute();

            return $result->getId();
        } catch (\Exception $e) {
            Log::error('Failed to sync activity to Outlook Calendar: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync events from Outlook Calendar to activities.
     */
    public function syncFromCalendar(CalendarConnection $connection): int
    {
        try {
            $graph = $this->getGraphClient($connection);

            $calendarId = $connection->calendar_id ?? 'calendar';
            $startDateTime = now()->subDays(7)->toIso8601String();
            $endDateTime = now()->addDays(30)->toIso8601String();

            $events = $graph->createRequest('GET', "/me/calendars/{$calendarId}/calendarView?startDateTime={$startDateTime}&endDateTime={$endDateTime}")
                ->execute();

            $syncedCount = 0;

            foreach ($events as $event) {
                if ($this->createActivityFromEvent($event, $connection)) {
                    $syncedCount++;
                }
            }

            $connection->update(['last_synced_at' => now()]);

            return $syncedCount;
        } catch (\Exception $e) {
            Log::error('Failed to sync from Outlook Calendar: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create activity from Outlook Calendar event.
     */
    protected function createActivityFromEvent($event, CalendarConnection $connection): bool
    {
        try {
            // Check if activity already exists
            $existing = Activity::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('created_by', $connection->user_id)
                ->whereJsonContains('metadata->outlook_event_id', $event->getId())
                ->first();

            if ($existing) {
                return false;
            }

            $start = $event->getStart();
            $dueDate = $start && $start->getDateTime() 
                ? new \DateTime($start->getDateTime()) 
                : null;

            Activity::create([
                'tenant_id' => $this->tenantContext->getTenantId(),
                'type' => 'meeting',
                'subject' => $event->getSubject() ?? 'Calendar Event',
                'description' => $event->getBody()?->getContent() ?? '',
                'due_date' => $dueDate,
                'priority' => 'medium',
                'status' => 'pending',
                'created_by' => $connection->user_id,
                'metadata' => [
                    'outlook_event_id' => $event->getId(),
                    'calendar_connection_id' => $connection->id,
                ],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create activity from event: ' . $e->getMessage());
            return false;
        }
    }
}


