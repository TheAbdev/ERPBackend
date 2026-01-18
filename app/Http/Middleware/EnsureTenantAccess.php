<?php

namespace App\Http\Middleware;

use App\Core\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantContext = app(TenantContext::class);

        // If no user is authenticated, let Sanctum handle it
        if (! $user) {
            return $next($request);
        }

        // If no tenant is resolved, deny access
        if (! $tenantContext->hasTenant()) {
            return response()->json([
                'message' => 'Tenant not resolved.',
            ], 400);
        }

        // CRITICAL: Block users from accessing another tenant
        if ($user->tenant_id !== $tenantContext->getTenantId()) {
            return response()->json([
                'message' => 'Unauthorized access to tenant.',
            ], 403);
        }

        return $next($request);
    }
}

