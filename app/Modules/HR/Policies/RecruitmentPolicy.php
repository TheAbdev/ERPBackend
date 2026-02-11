<?php

namespace App\Modules\HR\Policies;

class RecruitmentPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'recruitments';
    }
}

