<?php

namespace App\Modules\ERP\Policies;

class DepreciationPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'assets.depreciation';
    }
}




