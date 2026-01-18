<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\CalendarConnection;
use App\Modules\CRM\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    protected GoogleCalendarService $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Get authorization URL for Google Calendar.
     */
    public function connect(Request $request): JsonResponse
    {
        $this->authorize('create', CalendarConnection::class);

        try {
            // Check if Google API client is available
            if (!class_exists('Google_Client')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google API client is not installed. Please install google/apiclient package.',
                ], 500);
            }

            // Check if Google credentials are configured
            if (!config('services.google.client_id') || !config('services.google.client_secret')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Calendar credentials are not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env file.',
                ], 500);
            }

            $url = $this->calendarService->getAuthorizationUrl(
                $request->user()->id,
                $request->user()->tenant_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'authorization_url' => $url,
                ],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get Google Calendar authorization URL: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get authorization URL: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $this->authorize('create', CalendarConnection::class);

        try {
            $connection = $this->calendarService->handleCallback(
                $request->input('code'),
                $request->user()->id,
                $request->user()->tenant_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Google Calendar connected successfully.',
                'data' => $connection,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Google Calendar: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Sync activities to Google Calendar.
     */
    public function sync(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CalendarConnection::class);

        $connection = CalendarConnection::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->where('provider', 'google')
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'No active Google Calendar connection found.',
            ], 404);
        }

        try {
            $syncedCount = $this->calendarService->syncFromCalendar($connection);

            return response()->json([
                'success' => true,
                'message' => "Synced {$syncedCount} events from Google Calendar.",
                'data' => [
                    'synced_count' => $syncedCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get calendar events.
     */
    public function events(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CalendarConnection::class);

        $connection = CalendarConnection::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->where('provider', 'google')
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'No active Google Calendar connection found.',
            ], 404);
        }

        // This would require additional method in GoogleCalendarService
        // For now, return connection info
        return response()->json([
            'success' => true,
            'data' => [
                'connection' => $connection,
            ],
        ]);
    }

    /**
     * Disconnect Google Calendar.
     */
    public function disconnect(Request $request, CalendarConnection $calendarConnection): JsonResponse
    {
        $this->authorize('delete', $calendarConnection);

        $calendarConnection->update(['is_active' => false]);
        $calendarConnection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Google Calendar disconnected successfully.',
        ]);
    }
}


