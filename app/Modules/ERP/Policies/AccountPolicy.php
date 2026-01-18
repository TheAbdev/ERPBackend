<?php

namespace App\Modules\ERP\Policies;

class AccountPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'accounting.accounts';
    }
}

