<?php

namespace App\Modules\HR\Policies;

class EmployeeDocumentPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'employee_documents';
    }
}

