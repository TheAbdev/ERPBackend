<?php

namespace App\Modules\ERP\Policies;

class PurchaseOrderPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'purchases';
    }
}

