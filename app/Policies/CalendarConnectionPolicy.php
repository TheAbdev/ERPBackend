<?php

namespace App\Policies;

use App\Modules\CRM\Models\CalendarConnection;

class CalendarConnectionPolicy extends BasePolicy
{
    protected function getResourceName(): string
    {
        return 'calendar_connections';
    }
}
