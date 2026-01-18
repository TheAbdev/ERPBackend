<?php

namespace App\Modules\ERP\Policies;

class InventorySerialPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'inventory_serials';
    }
}

