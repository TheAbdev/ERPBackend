<?php

namespace App\Modules\ERP\Policies;

class ProductCategoryPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'product_categories';
    }
}

