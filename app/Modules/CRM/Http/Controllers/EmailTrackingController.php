<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Services\EmailTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    protected EmailTrackingService $trackingService;

    public function __construct(EmailTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Track email open (tracking pixel).
     */
    public function trackOpen(Request $request, string $token): Response
    {
        $this->trackingService->recordOpen($token);

        // Return 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Track email click and redirect.
     */
    public function trackClick(Request $request, string $token): \Illuminate\Http\RedirectResponse
    {
        $url = $request->query('url');
        
        if (!$url) {
            abort(404);
        }

        $originalUrl = $this->trackingService->recordClick($token, $url);

        if ($originalUrl) {
            return redirect($originalUrl);
        }

        abort(404);
    }
}





