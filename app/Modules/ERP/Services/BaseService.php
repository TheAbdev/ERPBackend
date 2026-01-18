<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;

/**
 * Base service class for all ERP services.
 */
abstract class BaseService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get the current tenant ID.
     *
     * @return int
     */
    protected function getTenantId(): int
    {
        return $this->tenantContext->getTenantId();
    }
}

