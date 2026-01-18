<?php

namespace App\Modules\ERP\Policies;

class TaxPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'taxes';
    }
}

