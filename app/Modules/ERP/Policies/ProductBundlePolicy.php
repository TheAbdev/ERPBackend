<?php

namespace App\Modules\ERP\Policies;

use App\Modules\ERP\Policies\ErpBasePolicy;
use App\Modules\ERP\Models\ProductBundle;
use App\Models\User;

class ProductBundlePolicy extends ErpBasePolicy
{
    /**
     * Determine if the user can view any product bundles.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'erp.products.viewAny');
    }

    /**
     * Determine if the user can view the product bundle.
     */
    public function view(User $user, ProductBundle $productBundle): bool
    {
        return $this->checkTenantAccess($user, $productBundle)
            && $this->checkPermission($user, 'erp.products.view');
    }

    /**
     * Determine if the user can create product bundles.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'erp.products.create');
    }

    /**
     * Determine if the user can update the product bundle.
     */
    public function update(User $user, ProductBundle $productBundle): bool
    {
        return $this->checkTenantAccess($user, $productBundle)
            && $this->checkPermission($user, 'erp.products.update');
    }

    /**
     * Determine if the user can delete the product bundle.
     */
    public function delete(User $user, ProductBundle $productBundle): bool
    {
        return $this->checkTenantAccess($user, $productBundle)
            && $this->checkPermission($user, 'erp.products.delete');
    }
}




