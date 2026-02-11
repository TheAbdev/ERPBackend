<?php

namespace App\Modules\HR\Policies;

class DepartmentPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'departments';
    }
}
