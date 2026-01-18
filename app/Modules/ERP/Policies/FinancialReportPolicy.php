<?php

namespace App\Modules\ERP\Policies;

class FinancialReportPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'reports';
    }
}

