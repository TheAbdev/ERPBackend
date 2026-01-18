<?php

namespace App\Modules\ERP\Policies;

class InvoicePolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'invoices';
    }
}

