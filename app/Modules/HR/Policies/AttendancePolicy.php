<?php

namespace App\Modules\HR\Policies;

class AttendancePolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'attendances';
    }
}

