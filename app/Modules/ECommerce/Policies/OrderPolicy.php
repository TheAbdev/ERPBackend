<?php

namespace App\Modules\ECommerce\Policies;

use App\Models\User;
use App\Modules\ECommerce\Models\Order;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ecommerce.orders.view');
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('ecommerce.orders.view') 
            && $user->tenant_id === $order->tenant_id;
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('ecommerce.orders.update') 
            && $user->tenant_id === $order->tenant_id;
    }
}



















