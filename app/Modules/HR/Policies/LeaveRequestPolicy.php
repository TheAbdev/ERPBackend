<?php

namespace App\Modules\HR\Policies;

class LeaveRequestPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'leave_requests';
    }
}
