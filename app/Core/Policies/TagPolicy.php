<?php

namespace App\Core\Policies;

use App\Policies\BasePolicy;

class TagPolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'tags';
    }

    protected function getModuleName(): string
    {
        return 'core';
    }
}

