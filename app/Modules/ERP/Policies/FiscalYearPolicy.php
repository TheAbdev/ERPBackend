<?php

namespace App\Modules\ERP\Policies;

class FiscalYearPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'fiscal_years';
    }
}

