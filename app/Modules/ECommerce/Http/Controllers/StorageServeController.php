<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
/**
 * Serve storage files via API so responses include CORS headers
 * (fixes OpaqueResponseBlocking when frontend loads images from another origin).
 */
class StorageServeController extends Controller
{
    public function __invoke(Request $request)
    {
        $path = $request->query('path');
        if (!$path || !is_string($path)) {
            return response()->json(['message' => 'Missing path'], 400);
        }

        // Only allow paths under storage/app/public (no directory traversal)
        $path = str_replace('\\', '/', $path);
        if (preg_match('#\.\.#', $path) || preg_match('#^/#', $path)) {
            return response()->json(['message' => 'Invalid path'], 400);
        }

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $mime = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($fullPath);
            if ($detected) {
                $mime = $detected;
            }
        }

        return response()->file($fullPath, [
            'Content-Type' => $mime,
        ]);
    }
}
