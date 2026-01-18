<?php

namespace App\Modules\ERP\Policies;

class PaymentPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'payments';
    }
}

