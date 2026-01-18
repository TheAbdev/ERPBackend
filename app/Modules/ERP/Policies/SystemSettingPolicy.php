<?php

namespace App\Modules\ERP\Policies;

use App\Modules\ERP\Models\SystemSetting;
use App\Models\User;
use App\Policies\BasePolicy;

class SystemSettingPolicy extends BasePolicy
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
     * Determine if the user can view any settings.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('erp.settings.view');
    }

    /**
     * Determine if the user can view the setting.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function view(User $user, $model): bool
    {
        return parent::view($user, $model);
    }

    /**
     * Determine if the user can create settings.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('erp.settings.create');
    }

    /**
     * Determine if the user can update the setting.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function update(User $user, $model): bool
    {
        return parent::update($user, $model);
    }

    /**
     * Determine if the user can delete the setting.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $model
     * @return bool
     */
    public function delete(User $user, $model): bool
    {
        return parent::delete($user, $model);
    }
}

