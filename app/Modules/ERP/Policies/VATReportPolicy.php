<?php

namespace App\Modules\ERP\Policies;

class VATReportPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'reports.vat';
    }
}

