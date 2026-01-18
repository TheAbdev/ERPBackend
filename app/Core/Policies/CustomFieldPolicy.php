<?php

namespace App\Core\Policies;

use App\Policies\BasePolicy;

class CustomFieldPolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'custom_fields';
    }

    protected function getModuleName(): string
    {
        return 'core';
    }
}

