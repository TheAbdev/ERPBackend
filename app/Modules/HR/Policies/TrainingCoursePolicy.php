<?php

namespace App\Modules\HR\Policies;

class TrainingCoursePolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'training_courses';
    }
}

