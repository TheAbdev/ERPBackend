<?php

namespace App\Modules\ECommerce\Policies;

use App\Models\User;
use App\Modules\ECommerce\Models\Theme;

class ThemePolicy
{
    /**
     * Determine if the user can view any themes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ecommerce.themes.view');
    }

    /**
     * Determine if the user can view the theme.
     */
    public function view(User $user, Theme $theme): bool
    {
        return $user->hasPermission('ecommerce.themes.view') 
            && $user->tenant_id === $theme->tenant_id;
    }

    /**
     * Determine if the user can create themes.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('ecommerce.themes.create');
    }

    /**
     * Determine if the user can update the theme.
     */
    public function update(User $user, Theme $theme): bool
    {
        return $user->hasPermission('ecommerce.themes.update') 
            && $user->tenant_id === $theme->tenant_id;
    }

    /**
     * Determine if the user can delete the theme.
     */
    public function delete(User $user, Theme $theme): bool
    {
        return $user->hasPermission('ecommerce.themes.delete') 
            && $user->tenant_id === $theme->tenant_id;
    }
}







