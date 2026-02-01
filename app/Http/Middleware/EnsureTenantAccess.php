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

        // Check if user is Site Owner - Site Owner can access everything
        $isSiteOwner = $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
        if ($isSiteOwner) {
            // Site Owner can access without tenant resolution
            return $next($request);
        }

        // If no tenant is resolved, try to resolve from user's tenant_id
        if (! $tenantContext->hasTenant()) {
            // If user has a tenant_id, use it to resolve the tenant
            if ($user->tenant_id) {
                $tenant = \App\Core\Models\Tenant::find($user->tenant_id);
                if ($tenant) {
                    $tenantContext->setTenant($tenant);
                    $request->attributes->set('tenant_id', $tenant->id);
                    return $next($request);
                }
            }
            
            return response()->json([
                'message' => 'Tenant not resolved.',
            ], 400);
        }

        $resolvedTenantId = $tenantContext->getTenantId();

        // Check if user has a tenant_id and it matches the resolved tenant
        if ($user->tenant_id && (int) $user->tenant_id !== (int) $resolvedTenantId) {
            return response()->json([
                'message' => 'Unauthorized access to tenant.',
            ], 403);
        }

        // If user doesn't have tenant_id, set it from resolved tenant
        // This allows users without tenant_id to access if tenant is resolved
        if (! $user->tenant_id) {
            $user->tenant_id = $resolvedTenantId;
        }

        return $next($request);
    }
}

