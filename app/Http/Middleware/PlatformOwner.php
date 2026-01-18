<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformOwner
{
    /**
     * Handle an incoming request.
     *
     * Ensures only Site Owner (platform owner) can access platform-level routes.
     * Site Owner is identified by:
     * - Having role 'site_owner'
     * - OR having permission 'platform.manage'
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        // Check if user is Site Owner
        $isSiteOwner = $user->hasRole('site_owner') || $user->hasPermission('platform.manage');

        if (! $isSiteOwner) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized. Only Platform Owner can access this resource.',
            ], 403);
        }

        return $next($request);
    }
}

