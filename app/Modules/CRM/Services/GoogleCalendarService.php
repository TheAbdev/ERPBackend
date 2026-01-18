<?php

namespace App\Modules\CRM\Services;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\CalendarConnection;
use App\Modules\CRM\Models\Activity;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get Google Client instance.
     */
    protected function getClient(CalendarConnection $connection): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->addScope(Google_Service_Calendar::CALENDAR);

        if ($connection->access_token) {
            $client->setAccessToken($connection->access_token);
        }

        if ($connection->isTokenExpired() && $connection->refresh_token) {
            $client->refreshToken($connection->refresh_token);
            $this->updateTokens($connection, $client->getAccessToken());
        }

        return $client;
    }

    /**
     * Update access and refresh tokens.
     */
    protected function updateTokens(CalendarConnection $connection, array $tokenData): void
    {
        $connection->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => isset($tokenData['expires_in']) 
                ? now()->addSeconds($tokenData['expires_in']) 
                : null,
        ]);
    }

    /**
     * Get authorization URL.
     */
    public function getAuthorizationUrl(int $userId, ?int $tenantId = null): string
    {
        // Check if Google API client is available
        if (!class_exists('Google_Client')) {
            throw new \Exception('Google API client is not installed. Please install google/apiclient package.');
        }

        // Check if Google credentials are configured
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Google Calendar credentials are not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env file.');
        }

        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        
        // Get tenant_id from parameter, tenant context, or authenticated user
        if (!$tenantId) {
            $tenantId = $this->tenantContext->getTenantId();
        }
        if (!$tenantId) {
            $user = auth()->user();
            $tenantId = $user?->tenant_id;
        }
        
        $client->setState(json_encode([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]));

        return $client->createAuthUrl();
    }

    /**
     * Handle OAuth callback and create connection.
     */
    public function handleCallback(string $code, int $userId, ?int $tenantId = null): CalendarConnection
    {
        // Check if Google API client is available
        if (!class_exists('Google_Client')) {
            throw new \Exception('Google API client is not installed. Please install google/apiclient package.');
        }

        // Check if Google credentials are configured
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('Google Calendar credentials are not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env file.');
        }

        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->fetchAccessTokenWithAuthCode($code);

        $tokenData = $client->getAccessToken();

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
            'provider' => 'google',
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'token_expires_at' => isset($tokenData['expires_in']) 
                ? now()->addSeconds($tokenData['expires_in']) 
                : null,
            'is_active' => true,
            'sync_enabled' => true,
        ]);
    }

    /**
     * Sync activities to Google Calendar.
     */
    public function syncActivityToCalendar(Activity $activity, CalendarConnection $connection): ?string
    {
        try {
            $client = $this->getClient($connection);
            $service = new Google_Service_Calendar($client);

            $event = new Google_Service_Calendar_Event();
            $event->setSummary($activity->subject);
            $event->setDescription($activity->description);

            if ($activity->due_date) {
                $start = new \Google_Service_Calendar_EventDateTime();
                $start->setDateTime($activity->due_date->toRfc3339String());
                $event->setStart($start);

                $end = new \Google_Service_Calendar_EventDateTime();
                $end->setDateTime($activity->due_date->addHours(1)->toRfc3339String());
                $event->setEnd($end);
            }

            $calendarId = $connection->calendar_id ?? 'primary';
            $createdEvent = $service->events->insert($calendarId, $event);

            return $createdEvent->getId();
        } catch (\Exception $e) {
            Log::error('Failed to sync activity to Google Calendar: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync events from Google Calendar to activities.
     */
    public function syncFromCalendar(CalendarConnection $connection): int
    {
        try {
            $client = $this->getClient($connection);
            $service = new Google_Service_Calendar($client);

            $calendarId = $connection->calendar_id ?? 'primary';
            $optParams = [
                'maxResults' => 100,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => now()->subDays(7)->toRfc3339String(),
            ];

            $events = $service->events->listEvents($calendarId, $optParams);
            $syncedCount = 0;

            foreach ($events->getItems() as $event) {
                if ($this->createActivityFromEvent($event, $connection)) {
                    $syncedCount++;
                }
            }

            $connection->update(['last_synced_at' => now()]);

            return $syncedCount;
        } catch (\Exception $e) {
            Log::error('Failed to sync from Google Calendar: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create activity from Google Calendar event.
     */
    protected function createActivityFromEvent($event, CalendarConnection $connection): bool
    {
        try {
            // Check if activity already exists (by external_id or event ID)
            $existing = Activity::where('tenant_id', $this->tenantContext->getTenantId())
                ->where('created_by', $connection->user_id)
                ->whereJsonContains('metadata->google_event_id', $event->getId())
                ->first();

            if ($existing) {
                return false;
            }

            $start = $event->getStart();
            $dueDate = $start->getDateTime() ?: $start->getDate();

            Activity::create([
                'tenant_id' => $this->tenantContext->getTenantId(),
                'type' => 'meeting',
                'subject' => $event->getSummary() ?? 'Calendar Event',
                'description' => $event->getDescription(),
                'due_date' => $dueDate ? new \DateTime($dueDate) : null,
                'priority' => 'medium',
                'status' => 'pending',
                'created_by' => $connection->user_id,
                'metadata' => [
                    'google_event_id' => $event->getId(),
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


