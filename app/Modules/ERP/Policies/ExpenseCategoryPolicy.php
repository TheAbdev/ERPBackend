<?php

namespace App\Modules\ERP\Policies;

class ExpenseCategoryPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'expense_categories';
    }
}

