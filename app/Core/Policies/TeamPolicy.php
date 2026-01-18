<?php

namespace App\Core\Policies;

use App\Policies\BasePolicy;

class TeamPolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'teams';
    }

    protected function getModuleName(): string
    {
        return 'core';
    }
}

