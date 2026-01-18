<?php

namespace App\Modules\CRM\Policies;

use App\Policies\BasePolicy;

class LeadAssignmentRulePolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'lead_assignment_rules';
    }

    protected function getModuleName(): string
    {
        return 'crm';
    }
}

