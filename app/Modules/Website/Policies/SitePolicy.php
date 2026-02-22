<?php

namespace App\Modules\Website\Policies;

use App\Modules\Website\Models\WebsiteSite;
use App\Core\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any sites.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('website.sites.viewAny');
    }

    /**
     * Determine if the user can view the site.
     */
    public function view(User $user, WebsiteSite $site): bool
    {
        // User can view their own tenant's site
        if ($user->tenant_id && $site->tenant_id === $user->tenant_id) {
            return true;
        }

        // Site Owner can view any site
        return $user->hasRole('site_owner') || $user->hasPermission('website.sites.view');
    }

    /**
     * Determine if the user can create sites.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('website.sites.create');
    }

    /**
     * Determine if the user can update the site.
     */
    public function update(User $user, WebsiteSite $site): bool
    {
        // User can update their own tenant's site
        if ($user->tenant_id && $site->tenant_id === $user->tenant_id) {
            return $user->hasPermission('website.sites.update');
        }

        // Site Owner can update any site
        return $user->hasRole('site_owner') || $user->hasPermission('website.sites.update');
    }

    /**
     * Determine if the user can delete the site.
     */
    public function delete(User $user, WebsiteSite $site): bool
    {
        // User can delete their own tenant's site
        if ($user->tenant_id && $site->tenant_id === $user->tenant_id) {
            return $user->hasPermission('website.sites.delete');
        }

        // Site Owner can delete any site
        return $user->hasRole('site_owner') || $user->hasPermission('website.sites.delete');
    }
}

