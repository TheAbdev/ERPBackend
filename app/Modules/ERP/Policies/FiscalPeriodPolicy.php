<?php

namespace App\Modules\ERP\Policies;

class FiscalPeriodPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'fiscal_periods';
    }
}

