<?php

namespace App\Modules\Website\Policies;

use App\Modules\Website\Models\WebsiteTemplate;
use App\Core\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('website.templates.viewAny');
    }

    /**
     * Determine if the user can view the template.
     */
    public function view(User $user, WebsiteTemplate $template): bool
    {
        // Global templates can be viewed by anyone with permission
        if (!$template->tenant_id) {
            return $user->hasPermission('website.templates.view');
        }

        // User can view their own tenant's templates
        if ($user->tenant_id && $template->tenant_id === $user->tenant_id) {
            return true;
        }

        // Site Owner can view any template
        return $user->hasRole('site_owner') || $user->hasPermission('website.templates.view');
    }

    /**
     * Determine if the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('website.templates.create');
    }

    /**
     * Determine if the user can update the template.
     */
    public function update(User $user, WebsiteTemplate $template): bool
    {
        // Global templates can only be updated by Site Owner
        if (!$template->tenant_id) {
            return $user->hasRole('site_owner') || $user->hasPermission('website.templates.update');
        }

        // User can update their own tenant's templates
        if ($user->tenant_id && $template->tenant_id === $user->tenant_id) {
            return $user->hasPermission('website.templates.update');
        }

        // Site Owner can update any template
        return $user->hasRole('site_owner') || $user->hasPermission('website.templates.update');
    }

    /**
     * Determine if the user can delete the template.
     */
    public function delete(User $user, WebsiteTemplate $template): bool
    {
        // Global templates cannot be deleted
        if (!$template->tenant_id) {
            return false;
        }

        // User can delete their own tenant's templates
        if ($user->tenant_id && $template->tenant_id === $user->tenant_id) {
            return $user->hasPermission('website.templates.delete');
        }

        // Site Owner can delete any tenant template
        return $user->hasRole('site_owner') || $user->hasPermission('website.templates.delete');
    }

    /**
     * Determine if the user can copy the template.
     */
    public function copy(User $user, WebsiteTemplate $template): bool
    {
        // Can copy global templates or own tenant's templates
        if (!$template->tenant_id) {
            return $user->hasPermission('website.templates.copy');
        }

        if ($user->tenant_id && $template->tenant_id === $user->tenant_id) {
            return $user->hasPermission('website.templates.copy');
        }

        return $user->hasRole('site_owner') || $user->hasPermission('website.templates.copy');
    }
}

