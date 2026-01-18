<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\CalendarConnection;
use App\Modules\CRM\Services\OutlookCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutlookCalendarController extends Controller
{
    protected OutlookCalendarService $calendarService;

    public function __construct(OutlookCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Get authorization URL for Outlook Calendar.
     */
    public function connect(Request $request): JsonResponse
    {
        $this->authorize('create', CalendarConnection::class);

        try {
            // Check if Microsoft credentials are configured
            if (!config('services.microsoft.client_id') || !config('services.microsoft.client_secret')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Microsoft Calendar credentials are not configured. Please set MICROSOFT_CLIENT_ID and MICROSOFT_CLIENT_SECRET in .env file.',
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
            \Illuminate\Support\Facades\Log::error('Failed to get Outlook Calendar authorization URL: ' . $e->getMessage(), [
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
                'message' => 'Outlook Calendar connected successfully.',
                'data' => $connection,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Outlook Calendar: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Sync activities to Outlook Calendar.
     */
    public function sync(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CalendarConnection::class);

        $connection = CalendarConnection::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->where('provider', 'outlook')
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'No active Outlook Calendar connection found.',
            ], 404);
        }

        try {
            $syncedCount = $this->calendarService->syncFromCalendar($connection);

            return response()->json([
                'success' => true,
                'message' => "Synced {$syncedCount} events from Outlook Calendar.",
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
     * Disconnect Outlook Calendar.
     */
    public function disconnect(Request $request, CalendarConnection $calendarConnection): JsonResponse
    {
        $this->authorize('delete', $calendarConnection);

        $calendarConnection->update(['is_active' => false]);
        $calendarConnection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Outlook Calendar disconnected successfully.',
        ]);
    }
}


