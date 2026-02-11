<?php

namespace App\Modules\HR\Policies;

class RecruitmentOpeningPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'recruitment_openings';
    }
}

