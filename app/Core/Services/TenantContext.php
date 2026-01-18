<?php

namespace App\Core\Services;

use App\Core\Models\Tenant;

class TenantContext
{
    /**
     * The current tenant instance.
     *
     * @var Tenant|null
     */
    protected ?Tenant $tenant = null;

    /**
     * Set the current tenant.
     *
     * @param  Tenant  $tenant
     * @return void
     */
    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the current tenant.
     *
     * @return Tenant|null
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get the current tenant ID.
     *
     * @return int|null
     */
    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    /**
     * Check if a tenant is set.
     *
     * @return bool
     */
    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Clear the current tenant context.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->tenant = null;
    }
}

