<?php

namespace App\Modules\HR\Policies;

class ContractPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'contracts';
    }
}

