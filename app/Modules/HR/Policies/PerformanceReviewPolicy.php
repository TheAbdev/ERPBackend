<?php

namespace App\Modules\HR\Policies;

class PerformanceReviewPolicy extends HrBasePolicy
{
    protected function getResourceName(): string
    {
        return 'performance_reviews';
    }
}
