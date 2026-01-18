<?php

namespace App\Modules\ERP\Policies;

class SupplierPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'suppliers';
    }
}



