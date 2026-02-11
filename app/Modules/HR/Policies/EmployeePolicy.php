<?php

namespace App\Modules\HR\Policies;

class EmployeePolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'employees';
    }
}
