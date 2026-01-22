<?php

namespace App\Modules\ECommerce\Policies;

use App\Models\User;
use App\Modules\ECommerce\Models\ProductSync;

class ProductSyncPolicy
{
    /**
     * Determine if the user can view any product syncs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ecommerce.stores.view');
    }

    /**
     * Determine if the user can create product syncs.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('ecommerce.stores.update');
    }

    /**
     * Determine if the user can update the product sync.
     */
    public function update(User $user, ProductSync $productSync): bool
    {
        return $user->hasPermission('ecommerce.stores.update') 
            && $user->tenant_id === $productSync->tenant_id;
    }
}





