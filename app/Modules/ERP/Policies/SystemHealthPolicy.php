<?php

namespace App\Modules\ERP\Policies;

use App\Modules\ERP\Models\SystemHealth;
use App\Models\User;
use App\Policies\BasePolicy;

class SystemHealthPolicy extends BasePolicy
{
    /**
     * Get the module name.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'erp';
    }

    /**
     * Determine if the user can view any health records.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('erp.system.health.view');
    }

    /**
     * Determine if the user can view the health record.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function view(User $user, $model): bool
    {
        if (!$user->hasPermission('erp.system.health.view')) {
            return false;
        }

        // If model has tenant_id, verify it matches user's tenant or is null (system-wide)
        if (isset($model->tenant_id)) {
            return $model->tenant_id === null || $user->tenant_id === $model->tenant_id;
        }

        return true;
    }
}

