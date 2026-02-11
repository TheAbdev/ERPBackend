<?php

namespace App\Modules\HR\Policies;

class TrainingPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'trainings';
    }
}

