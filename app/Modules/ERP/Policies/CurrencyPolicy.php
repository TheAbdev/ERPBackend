<?php

namespace App\Modules\ERP\Policies;

class CurrencyPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'currencies';
    }
}












