<?php

namespace App\Modules\HR\Policies;

class TrainingAssignmentPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'training_assignments';
    }
}

