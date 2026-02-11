<?php

namespace App\Modules\HR\Policies;

class TrainingEnrollmentPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'training_enrollments';
    }
}

