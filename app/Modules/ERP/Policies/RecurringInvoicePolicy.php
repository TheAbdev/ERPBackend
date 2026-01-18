<?php

namespace App\Modules\ERP\Policies;

class RecurringInvoicePolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'recurring_invoices';
    }
}

