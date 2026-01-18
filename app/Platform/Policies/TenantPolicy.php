<?php

namespace App\Platform\Policies;

use App\Core\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    /**
     * Determine if user can view any tenants.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }

    /**
     * Determine if user can view the tenant.
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }

    /**
     * Determine if user can create tenants.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }

    /**
     * Determine if user can update the tenant.
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }

    /**
     * Determine if user can delete the tenant.
     */
    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }

    /**
     * Determine if user can assign owner to tenant.
     */
    public function assignOwner(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }

    /**
     * Determine if user can activate tenant.
     */
    public function activate(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }

    /**
     * Determine if user can suspend tenant.
     */
    public function suspend(User $user, Tenant $tenant): bool
    {
        return $user->hasRole('site_owner') || $user->hasPermission('platform.manage');
    }
}

