<?php

namespace App\Modules\ERP\Policies;

class ProductPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'products';
    }
}

