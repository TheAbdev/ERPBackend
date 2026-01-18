<?php

namespace App\Listeners;

use App\Core\Services\PermissionCacheService;
use App\Core\Models\Role;
use App\Core\Models\Permission;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ClearPermissionCache
{
    protected PermissionCacheService $permissionCacheService;

    public function __construct(PermissionCacheService $permissionCacheService)
    {
        $this->permissionCacheService = $permissionCacheService;
    }

    /**
     * Handle role updated event.
     */
    public function handleRoleUpdated(Role $role): void
    {
        // Clear all user caches for this tenant (role changes affect all users)
        $this->permissionCacheService->clearTenantPermissionCache($role->tenant_id);
    }

    /**
     * Handle permission updated event.
     */
    public function handlePermissionUpdated(Permission $permission): void
    {
        // Clear all user caches for this tenant
        $this->permissionCacheService->clearTenantPermissionCache($permission->tenant_id);
    }

    /**
     * Handle user role attached/detached event.
     */
    public function handleUserRoleChanged(User $user): void
    {
        $this->permissionCacheService->clearUserCache($user);
    }
}
