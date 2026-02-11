<?php

namespace App\Modules\HR\Policies;

class PayrollItemPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'payroll_items';
    }
}

