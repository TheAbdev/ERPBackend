<?php

namespace App\Modules\HR\Policies;

class AttendanceRecordPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'attendance_records';
    }
}

