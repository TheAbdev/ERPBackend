<?php

namespace App\Modules\CRM\Policies;

use App\Policies\BasePolicy;

class WorkflowPolicy extends BasePolicy
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
        return 'workflows';
    }
}





