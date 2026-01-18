<?php

namespace App\Core\Policies;

use App\Models\User;
use App\Policies\BasePolicy;

class UserPolicy extends BasePolicy
{
    /**
     * Get the resource name for this policy.
     */
    protected function getResourceName(): string
    {
        return 'users';
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('core.users.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $model): bool
    {
        if (!($model instanceof User)) {
            return false;
        }

        // Users can view themselves
        if ($user->id === $model->id) {
            return true;
        }

        // Check permission and tenant access
        return $user->hasPermission('core.users.view') &&
               $user->tenant_id === $model->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('core.users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $model): bool
    {
        if (!($model instanceof User)) {
            return false;
        }

        // Users can update themselves
        if ($user->id === $model->id) {
            return true;
        }

        // Check permission and tenant access
        return $user->hasPermission('core.users.update') &&
               $user->tenant_id === $model->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $model): bool
    {
        if (!($model instanceof User)) {
            return false;
        }

        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Check permission and tenant access
        return $user->hasPermission('core.users.delete') &&
               $user->tenant_id === $model->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $model): bool
    {
        if (!($model instanceof User)) {
            return false;
        }

        return $user->hasPermission('core.users.restore') &&
               $user->tenant_id === $model->tenant_id;
    }
}

