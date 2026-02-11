<?php

namespace App\Modules\HR\Policies;

class PayrollPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'payrolls';
    }
}

