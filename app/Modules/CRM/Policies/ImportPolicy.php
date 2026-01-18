<?php

namespace App\Modules\CRM\Policies;

use App\Policies\BasePolicy;

class ImportPolicy extends BasePolicy
{
    protected function getModuleName(): string
    {
        return 'crm';
    }

    protected function getResourceName(): string
    {
        return 'import';
    }
}





