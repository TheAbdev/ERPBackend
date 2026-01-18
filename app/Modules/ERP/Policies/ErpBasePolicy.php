<?php

namespace App\Modules\ERP\Policies;

use App\Policies\BasePolicy;

/**
 * Base policy for all ERP models.
 */
abstract class ErpBasePolicy extends BasePolicy
{
    /**
     * Get the module name.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'erp';
    }
}

