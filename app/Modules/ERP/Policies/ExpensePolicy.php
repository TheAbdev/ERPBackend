<?php

namespace App\Modules\ERP\Policies;

class ExpensePolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'expenses';
    }
}

