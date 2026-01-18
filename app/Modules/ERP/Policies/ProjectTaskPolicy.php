<?php

namespace App\Modules\ERP\Policies;

class ProjectTaskPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'project-tasks';
    }
}

