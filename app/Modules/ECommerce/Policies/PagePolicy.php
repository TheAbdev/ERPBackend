<?php

namespace App\Modules\ECommerce\Policies;

use App\Models\User;
use App\Modules\ECommerce\Models\Page;

class PagePolicy
{
    /**
     * Determine if the user can view any pages.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ecommerce.pages.view');
    }

    /**
     * Determine if the user can view the page.
     */
    public function view(User $user, Page $page): bool
    {
        return $user->hasPermission('ecommerce.pages.view') 
            && $user->tenant_id === $page->tenant_id;
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
     */
    public function update(User $user, Page $page): bool
    {
        return $user->hasPermission('ecommerce.pages.update') 
            && $user->tenant_id === $page->tenant_id;
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







