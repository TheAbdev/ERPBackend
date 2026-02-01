<?php

namespace App\Http\Middleware;

use App\Core\Models\Tenant;
use App\Core\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);

        // Skip tenant resolution for login route (Site Owner can login without tenant)
        if ($request->is('api/auth/login')) {
            return $next($request);
        }

        // Check if user is Site Owner - Site Owner doesn't need tenant resolution
        $user = $request->user();
        if ($user) {
            $isSiteOwner = $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
            if ($isSiteOwner) {
                // Site Owner can access without tenant
                // But we still try to resolve tenant if provided (for backward compatibility)
                $tenant = $this->resolveTenant($request);
                if ($tenant) {
                    $tenantContext->setTenant($tenant);
                    $request->attributes->set('tenant_id', $tenant->id);
                }
                return $next($request);
            }
        }

        // Try to resolve tenant from various sources
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant not found or invalid.',
            ], 404);
        }

        if (! $tenant->isActive()) {
            return response()->json([
                'message' => 'Tenant is not active.',
            ], 403);
        }

        // Set tenant in context
        $tenantContext->setTenant($tenant);

        // Add tenant_id to request attributes for easy access
        $request->attributes->set('tenant_id', $tenant->id);

        return $next($request);
    }

    /**
     * Resolve tenant from request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Tenant|null
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        // Priority 1: Check custom header (X-Tenant-ID) - HIGHEST PRIORITY
        // This is the most reliable way, especially for cross-domain requests
        if ($request->hasHeader('X-Tenant-ID')) {
            $tenantId = $request->header('X-Tenant-ID');
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                return $tenant;
            }
        }

        // Priority 2: Check X-Tenant-Slug header
        $tenantSlug = $request->header('X-Tenant-Slug') ?? $request->header('X-Tenant');
        if ($tenantSlug) {
            return Tenant::where('slug', $tenantSlug)->first();
        }

        // Priority 3: Check subdomain
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain) {
            $tenant = Tenant::where('subdomain', $subdomain)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // Priority 4: Check custom domain
        $tenant = Tenant::where('domain', $host)->first();
        if ($tenant) {
            return $tenant;
        }

        // Priority 5: If user is authenticated and has tenant_id, use that
        $user = $request->user();
        if ($user && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Extract subdomain from host.
     *
     * @param  string  $host
     * @return string|null
     */
    protected function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);

        // If we have at least 3 parts (subdomain.domain.tld), return the subdomain
        if (count($parts) >= 3) {
            return $parts[0];
        }

        // For local development (e.g., tenant1.localhost)
        if (count($parts) === 2 && $parts[1] === 'localhost') {
            return $parts[0];
        }

        return null;
    }
}

