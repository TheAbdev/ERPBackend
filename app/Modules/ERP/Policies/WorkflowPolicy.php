<?php

namespace App\Modules\ERP\Policies;

class WorkflowPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'workflows';
    }
}

