<?php

namespace App\Modules\ERP\Policies;

use App\Modules\ERP\Models\Report;
use App\Models\User;
use App\Policies\BasePolicy;

class ReportPolicy extends BasePolicy
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
     * Determine if the user can view any reports.
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
        
        return $user->hasPermission('erp.reports.view');
    }

    /**
     * Determine if the user can view the report.
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
     * Determine if the user can create reports.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('erp.reports.create');
    }

    /**
     * Determine if the user can update the report.
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
     * Determine if the user can delete the report.
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

