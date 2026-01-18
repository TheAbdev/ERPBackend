<?php

namespace App\Modules\CRM\Policies;

use App\Models\User;
use App\Modules\CRM\Models\Activity;
use App\Policies\BasePolicy;

class ActivityPolicy extends BasePolicy
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
        return 'activities';
    }
}

