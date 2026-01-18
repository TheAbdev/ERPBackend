<?php

namespace App\Policies;

use App\Core\Services\TenantContext;
use App\Models\User;

abstract class BasePolicy
{
    /**
     * Determine if the user can view any models.
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
     * Determine if the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function view(User $user, $model): bool
    {
        return $this->checkTenantAccess($user, $model)
            && $this->checkPermission($user, $this->getPermissionName('view'));
    }

    /**
     * Determine if the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        // Super Admin (Tenant Owner) has full access
        if ($user->tenant_id) {
            // Check if user has super_admin role
            try {
                if ($user->hasRole('super_admin')) {
                    return true;
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking super_admin role', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Fallback: Check if user is the tenant owner directly
            try {
                if (!$user->relationLoaded('tenant')) {
                    $user->load('tenant');
                }
                if ($user->tenant && $user->tenant->owner_user_id === $user->id) {
                    return true;
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking tenant owner', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Check permission
        $permission = $this->getPermissionName('create');
        return $this->checkPermission($user, $permission);
    }

    /**
     * Determine if the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function update(User $user, $model): bool
    {
        // Super Admin (Tenant Owner) has full access within their tenant
        if ($user->tenant_id) {
            // Check if user has super_admin role
            try {
                if ($user->hasRole('super_admin')) {
                    return $this->checkTenantAccess($user, $model);
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking super_admin role in update', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            // Fallback: Check if user is the tenant owner directly
            try {
                if (!$user->relationLoaded('tenant')) {
                    $user->load('tenant');
                }
                if ($user->tenant && $user->tenant->owner_user_id === $user->id) {
                    return $this->checkTenantAccess($user, $model);
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Illuminate\Support\Facades\Log::warning('Error checking tenant owner in update', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $this->checkTenantAccess($user, $model)
            && $this->checkPermission($user, $this->getPermissionName('update'));
    }

    /**
     * Determine if the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function delete(User $user, $model): bool
    {
        // Super Admin (Tenant Owner) has full access within their tenant
        if ($user->hasRole('super_admin')) {
            return $this->checkTenantAccess($user, $model);
        }
        
        return $this->checkTenantAccess($user, $model)
            && $this->checkPermission($user, $this->getPermissionName('delete'));
    }

    /**
     * Determine if the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function restore(User $user, $model): bool
    {
        return $this->checkTenantAccess($user, $model)
            && $this->checkPermission($user, $this->getPermissionName('restore'));
    }

    /**
     * Check if user has permission.
     *
     * @param  \App\Models\User  $user
     * @param  string  $permission
     * @return bool
     */
    protected function checkPermission(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /**
     * Check if user can access the model's tenant.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    protected function checkTenantAccess(User $user, $model): bool
    {
        // If model has tenant_id, verify it matches user's tenant
        if (isset($model->tenant_id)) {
            return $user->tenant_id === $model->tenant_id;
        }

        // If no tenant_id on model, allow (for system-wide resources)
        return true;
    }

    /**
     * Get the permission name for an action.
     * Override this method in child policies to customize permission naming.
     *
     * @param  string  $action
     * @return string
     */
    protected function getPermissionName(string $action): string
    {
        $resource = $this->getResourceName();
        $module = $this->getModuleName();

        return "{$module}.{$resource}.{$action}";
    }

    /**
     * Get the resource name from the policy class name.
     *
     * @return string
     */
    protected function getResourceName(): string
    {
        $className = class_basename($this);

        // Remove "Policy" suffix and convert to snake_case
        $resource = str_replace('Policy', '', $className);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $resource));
    }

    /**
     * Get the module name.
     * Override this method in child policies to specify module.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'core';
    }
}

