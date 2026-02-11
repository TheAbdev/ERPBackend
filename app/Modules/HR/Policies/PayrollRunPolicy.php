<?php

namespace App\Modules\HR\Policies;

class PayrollRunPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'payroll_runs';
    }
}

