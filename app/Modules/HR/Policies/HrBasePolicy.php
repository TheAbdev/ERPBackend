<?php

namespace App\Modules\HR\Policies;

use App\Policies\BasePolicy;

/**
 * Base policy for all HR models.
 */
abstract class HrBasePolicy extends BasePolicy
{
    protected function getModuleName(): string
    {
        return 'hr';
    }
}
