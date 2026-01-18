<?php

namespace App\Modules\CRM\Policies;

use App\Policies\BasePolicy;

class LeadScorePolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'lead_scores';
    }

    protected function getModuleName(): string
    {
        return 'crm';
    }
}

