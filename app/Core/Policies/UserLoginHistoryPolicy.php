<?php

namespace App\Core\Policies;

use App\Policies\BasePolicy;

class UserLoginHistoryPolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'user_login_history';
    }

    protected function getModuleName(): string
    {
        return 'core';
    }
}

