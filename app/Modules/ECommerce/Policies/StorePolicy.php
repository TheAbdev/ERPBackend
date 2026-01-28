<?php

namespace App\Modules\ECommerce\Policies;

use App\Models\User;
use App\Modules\ECommerce\Models\Store;

class StorePolicy
{
    /**
     * Determine if the user can view any stores.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ecommerce.stores.view');
    }

    /**
     * Determine if the user can view the store.
     */
    public function view(User $user, Store $store): bool
    {
        return $user->hasPermission('ecommerce.stores.view') 
            && $user->tenant_id === $store->tenant_id;
    }

    /**
     * Determine if the user can create stores.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('ecommerce.stores.create');
    }

    /**
     * Determine if the user can update the store.
     */
    public function update(User $user, Store $store): bool
    {
        return $user->hasPermission('ecommerce.stores.update') 
            && $user->tenant_id === $store->tenant_id;
    }

    /**
     * Determine if the user can delete the store.
     */
    public function delete(User $user, Store $store): bool
    {
        return $user->hasPermission('ecommerce.stores.delete') 
            && $user->tenant_id === $store->tenant_id;
    }
}



















