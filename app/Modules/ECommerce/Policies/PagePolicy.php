<?php

namespace App\Modules\ECommerce\Policies;

use App\Models\User;
use App\Modules\ECommerce\Models\Page;

class PagePolicy
{
    /**
     * Determine if the user can view any pages.
     * Allow if user can view OR create (who can add page can list/view).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ecommerce.pages.view')
            || $user->hasPermission('ecommerce.pages.create');
    }

    /**
     * Determine if the user can view the page.
     * Allow if user can view OR create, and page belongs to same tenant.
     */
    public function view(User $user, Page $page): bool
    {
        if ($user->tenant_id !== $page->tenant_id) {
            return false;
        }
        return $user->hasPermission('ecommerce.pages.view')
            || $user->hasPermission('ecommerce.pages.create');
    }

    /**
     * Determine if the user can create pages.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('ecommerce.pages.create');
    }

    /**
     * Determine if the user can update the page.
     * Allow if user can update OR create (who can add page can edit it).
     */
    public function update(User $user, Page $page): bool
    {
        if ($user->tenant_id !== $page->tenant_id) {
            return false;
        }
        return $user->hasPermission('ecommerce.pages.update')
            || $user->hasPermission('ecommerce.pages.create');
    }

    /**
     * Determine if the user can delete the page.
     */
    public function delete(User $user, Page $page): bool
    {
        return $user->hasPermission('ecommerce.pages.delete')
            && $user->tenant_id === $page->tenant_id;
    }
}



















