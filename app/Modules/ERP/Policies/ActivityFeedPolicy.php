<?php

namespace App\Modules\ERP\Policies;

class ActivityFeedPolicy extends ErpBasePolicy
{
    protected function getResourceName(): string
    {
        return 'activity_feed';
    }
}

