<?php

namespace App\Modules\ERP\Policies;

class SalesOrderPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'sales';
    }
}

