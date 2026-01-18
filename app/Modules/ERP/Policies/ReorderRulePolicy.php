<?php

namespace App\Modules\ERP\Policies;

class ReorderRulePolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'reorder_rules';
    }
}

