<?php

namespace App\Modules\ERP\Policies;

class ProjectPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'projects';
    }
}


