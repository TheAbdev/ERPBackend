<?php

namespace App\Modules\HR\Policies;

class EmploymentContractPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'employment_contracts';
    }
}

