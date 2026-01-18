<?php

namespace App\Core\Policies;

use App\Core\Models\Role;
use App\Models\User;
use App\Policies\BasePolicy;

class RolePolicy extends BasePolicy
{
    protected function getModuleName(): string
    {
        return 'core';
    }

    protected function getResourceName(): string
    {
        return 'roles';
    }

    /**
     * Determine whether the user can view any models.
     * Tenant Owner (super_admin) has full access.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Super Admin (Tenant Owner) has full access
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        return $this->checkPermission($user, $this->getPermissionName('viewAny'));
    }

    /**
     * Determine whether the user can create models.
     * Tenant Owner (super_admin) has full access.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Super Admin (Tenant Owner) has full access
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        return $this->checkPermission($user, $this->getPermissionName('create'));
    }

    /**
     * Determine whether the user can update the model.
     * Prevent updating system roles unless user has platform.manage permission.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Core\Models\Role  $role
     * @return bool
     */
    public function update(User $user, $role): bool
    {
        if (!($role instanceof Role)) {
            return false;
        }

        if ($role->is_system && !$user->hasPermission('platform.manage')) {
            return false;
        }

        return $this->checkTenantAccess($user, $role)
            && $this->checkPermission($user, $this->getPermissionName('update'));
    }

    /**
     * Determine whether the user can delete the model.
     * Prevent deleting system roles.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Core\Models\Role  $role
     * @return bool
     */
    public function delete(User $user, $role): bool
    {
        if (!($role instanceof Role)) {
            return false;
        }

        if ($role->is_system) {
            return false;
        }

        return $this->checkTenantAccess($user, $role)
            && $this->checkPermission($user, $this->getPermissionName('delete'));
    }
}

