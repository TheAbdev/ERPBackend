<?php

namespace App\Modules\HR\Policies;

class PositionPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'positions';
    }
}
