<?php

namespace App\Modules\ERP\Policies;

class FixedAssetPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'assets';
    }
}




