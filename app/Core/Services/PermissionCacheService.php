<?php

namespace App\Core\Services;

use App\Core\Models\Permission;
use App\Core\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class PermissionCacheService
{
    protected CacheService $cacheService;
    protected TenantContext $tenantContext;

    public function __construct(CacheService $cacheService, TenantContext $tenantContext)
    {
        $this->cacheService = $cacheService;
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get user permissions (cached).
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Support\Collection
     */
    public function getUserPermissions(User $user): Collection
    {
        return $this->cacheService->remember(
            "user:{$user->id}:permissions",
            config('performance.permission_cache_ttl', 3600),
            function () use ($user) {
                return $this->loadUserPermissions($user);
            },
            $user->tenant_id
        );
    }

    /**
     * Get user roles (cached).
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Support\Collection
     */
    public function getUserRoles(User $user): Collection
    {
        return $this->cacheService->remember(
            "user:{$user->id}:roles",
            config('performance.permission_cache_ttl', 3600),
            function () use ($user) {
                return $user->roles()->get();
            },
            $user->tenant_id
        );
    }

    /**
     * Check if user has permission (cached).
     *
     * @param  \App\Models\User  $user
     * @param  string  $permission
     * @return bool
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        $permissions = $this->getUserPermissions($user);
        return $permissions->contains('name', $permission);
    }

    /**
     * Load user permissions from database.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Support\Collection
     */
    protected function loadUserPermissions(User $user): Collection
    {
        return Permission::whereHas('roles', function ($query) use ($user) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        })
        ->where('permissions.tenant_id', $user->tenant_id)
        ->get();
    }

    /**
     * Clear user permission cache.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function clearUserCache(User $user): void
    {
        $this->cacheService->forget("user:{$user->id}:permissions", $user->tenant_id);
        $this->cacheService->forget("user:{$user->id}:roles", $user->tenant_id);
    }

    /**
     * Clear all permission caches for tenant.
     *
     * @param  int|null  $tenantId
     * @return void
     */
    public function clearTenantPermissionCache(?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();
        $this->cacheService->forgetPattern("user:*:permissions", $tenantId);
        $this->cacheService->forgetPattern("user:*:roles", $tenantId);
    }

    /**
     * Warm up permission cache for all users in tenant.
     *
     * @param  int|null  $tenantId
     * @return int Number of users cached
     */
    public function warmUpTenantCache(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();
        $users = User::where('tenant_id', $tenantId)->get();
        $count = 0;

        foreach ($users as $user) {
            $this->getUserPermissions($user);
            $this->getUserRoles($user);
            $count++;
        }

        return $count;
    }

    /**
     * Get all permissions (cached).
     *
     * @param  int|null  $tenantId
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions(?int $tenantId = null): Collection
    {
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();

        return $this->cacheService->remember(
            'permissions:all',
            config('performance.permission_cache_ttl', 3600),
            function () use ($tenantId) {
                return Permission::where('tenant_id', $tenantId)->get();
            },
            $tenantId
        );
    }

    /**
     * Get all roles (cached).
     *
     * @param  int|null  $tenantId
     * @return \Illuminate\Support\Collection
     */
    public function getAllRoles(?int $tenantId = null): Collection
    {
        $tenantId = $tenantId ?? $this->tenantContext->getTenantId();

        return $this->cacheService->remember(
            'roles:all',
            config('performance.permission_cache_ttl', 3600),
            function () use ($tenantId) {
                return Role::where('tenant_id', $tenantId)->get();
            },
            $tenantId
        );
    }
}

