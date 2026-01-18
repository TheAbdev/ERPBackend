<?php

namespace App\Modules\ERP\Policies;

class InventoryPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'inventory';
    }
}

