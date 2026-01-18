<?php

namespace App\Modules\ERP\Policies;

use App\Modules\ERP\Models\PaymentGateway;

class PaymentGatewayPolicy extends ErpBasePolicy
{
    /**
     * Get the resource name.
     *
     * @return string
     */
    protected function getResourceName(): string
    {
        return 'payment_gateways';
    }
}

