<?php

namespace App\Modules\CRM\Policies;

use App\Modules\CRM\Models\EmailAccount;

class EmailAccountPolicy extends \App\Policies\BasePolicy
{
    protected function getResourceName(): string
    {
        return 'email_accounts';
    }
}






