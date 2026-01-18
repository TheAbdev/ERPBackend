<?php

namespace App\Modules\CRM\Policies;

use App\Models\User;
use App\Modules\CRM\Models\Deal;
use App\Policies\BasePolicy;

class DealPolicy extends BasePolicy
{
    /**
     * Get the module name.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'crm';
    }

    /**
     * Get the resource name.
     *
     * @return string
     */
    protected function getResourceName(): string
    {
        return 'deals';
    }
}

