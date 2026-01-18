<?php

namespace App\Platform\Services;

use App\Core\Models\Role;
use App\Core\Models\Tenant;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\FiscalYear;
use App\Modules\ERP\Models\NumberSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantManagementService
{
    /**
     * Create a new tenant with optional owner.
     *
     * @param  array  $data
     * @return Tenant
     */
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);

                // Ensure uniqueness
                $originalSlug = $data['slug'];
                $counter = 1;
                while (Tenant::where('slug', $data['slug'])->exists()) {
                    $data['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Set default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'active';
            }

            // Create tenant
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'subdomain' => $data['subdomain'] ?? null,
                'domain' => $data['domain'] ?? null,
                'status' => $data['status'],
                'settings' => $data['settings'] ?? [],
            ]);

            // Create default number sequences for the tenant
            $this->createDefaultNumberSequences($tenant);

            // Create default fiscal year and periods for the tenant
            $this->createDefaultFiscalYear($tenant);

            // Assign owner if provided
            if (!empty($data['owner_user_id']) || !empty($data['owner_email'])) {
                $this->assignOwner($tenant, $data);
            }

            return $tenant->fresh(['owner']);
        });
    }

    /**
     * Update tenant information.
     *
     * @param  Tenant  $tenant
     * @param  array  $data
     * @return Tenant
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $tenant->update($data);
            return $tenant->fresh(['owner']);
        });
    }

    /**
     * Assign owner to tenant.
     *
     * @param  Tenant  $tenant
     * @param  array  $data
     * @return Tenant
     */
    public function assignOwner(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            // Find or create user
            $user = null;

            // Check if we need to create a new user
            if (!empty($data['owner_name']) && !empty($data['owner_email']) && !empty($data['owner_password'])) {
                // Create new user
                $user = User::create([
                    'name' => $data['owner_name'],
                    'email' => $data['owner_email'],
                    'password' => Hash::make($data['owner_password']),
                    'tenant_id' => $tenant->id,
                    'email_verified_at' => now(),
                ]);
            } else {
                // Find existing user by ID or email
                if (!empty($data['user_id'])) {
                    $user = User::find($data['user_id']);
                } elseif (!empty($data['email']) || !empty($data['owner_email'])) {
                    $email = $data['email'] ?? $data['owner_email'];
                    $user = User::where('email', $email)->first();
                }

                if (! $user) {
                    throw new \InvalidArgumentException('User not found. Provide owner_name, owner_email, and owner_password to create a new user.');
                }

                // Ensure user belongs to this tenant
                if ($user->tenant_id !== $tenant->id) {
                    // Move user to this tenant
                    $user->update(['tenant_id' => $tenant->id]);
                }
            }

            // Ensure super_admin role exists for this tenant
            $superAdminRole = Role::where('tenant_id', $tenant->id)
                ->where('slug', 'super_admin')
                ->first();

            if (! $superAdminRole) {
                // Create super_admin role if it doesn't exist
                $superAdminRole = Role::create([
                    'tenant_id' => $tenant->id,
                    'slug' => 'super_admin',
                    'name' => 'Super Admin',
                    'description' => 'Tenant owner with full access to manage the tenant',
                    'is_system' => true,
                ]);
            }

            // Always sync all permissions to super_admin role (tenant-level permissions only)
            // This ensures new permissions are automatically added to existing super_admin roles
            // Get all permissions that are not platform-level (exclude platform.manage, core.tenants.*, and core.audit_logs.*)
            $allPermissions = \App\Core\Models\Permission::where('slug', '!=', 'platform.manage')
                ->where('slug', 'not like', 'core.tenants.%')
                ->where('slug', 'not like', 'core.audit_logs.%')
                ->get();
            if ($allPermissions->isNotEmpty()) {
                $superAdminRole->permissions()->sync($allPermissions->pluck('id')->toArray());
            }

            // Assign Super Admin role if not already assigned
            if (! $user->hasRole('super_admin')) {
                $user->roles()->attach($superAdminRole->id, [
                    'tenant_id' => $tenant->id,
                ]);
            }

            // Update tenant owner
            $tenant->update(['owner_user_id' => $user->id]);

            // Send welcome email if this is a new user
            if (!empty($data['owner_password'])) {
                try {
                    Mail::to($user->email)->send(new WelcomeUserMail($user, $data['owner_password']));
                } catch (\Exception $e) {
                    // Log error but don't fail the request
                    \Illuminate\Support\Facades\Log::error('Failed to send welcome email to tenant owner: ' . $e->getMessage());
                }
            }

            return $tenant->fresh(['owner']);
        });
    }

    /**
     * Activate tenant.
     *
     * @param  Tenant  $tenant
     * @return Tenant
     */
    public function activateTenant(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active']);
            return $tenant->fresh(['owner']);
        });
    }

    /**
     * Suspend tenant.
     *
     * @param  Tenant  $tenant
     * @return Tenant
     */
    public function suspendTenant(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'suspended']);
            return $tenant->fresh(['owner']);
        });
    }

    /**
     * Delete tenant (soft delete).
     *
     * @param  Tenant  $tenant
     * @return bool
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            return $tenant->delete();
        });
    }

    /**
     * Create default number sequences for a tenant.
     *
     * @param  Tenant  $tenant
     * @return void
     */
    protected function createDefaultNumberSequences(Tenant $tenant): void
    {
        $defaultSequences = [
            [
                'code' => 'sales_order',
                'name' => 'Sales Order',
                'prefix' => 'SO',
                'format' => '{PREFIX}-{YYYY}-{NUMBER}',
                'min_length' => 5,
                'next_number' => 1,
            ],
            [
                'code' => 'purchase_order',
                'name' => 'Purchase Order',
                'prefix' => 'PO',
                'format' => '{PREFIX}-{YYYY}-{NUMBER}',
                'min_length' => 5,
                'next_number' => 1,
            ],
            [
                'code' => 'sales_invoice',
                'name' => 'Sales Invoice',
                'prefix' => 'INV',
                'format' => '{PREFIX}-{YYYY}-{NUMBER}',
                'min_length' => 5,
                'next_number' => 1,
            ],
            [
                'code' => 'purchase_invoice',
                'name' => 'Purchase Invoice',
                'prefix' => 'PINV',
                'format' => '{PREFIX}-{YYYY}-{NUMBER}',
                'min_length' => 5,
                'next_number' => 1,
            ],
            [
                'code' => 'payment',
                'name' => 'Payment',
                'prefix' => 'PAY',
                'format' => '{PREFIX}-{YYYY}-{NUMBER}',
                'min_length' => 5,
                'next_number' => 1,
            ],
            [
                'code' => 'journal_entry',
                'name' => 'Journal Entry',
                'prefix' => 'JE',
                'format' => '{PREFIX}-{YYYY}-{NUMBER}',
                'min_length' => 5,
                'next_number' => 1,
            ],
        ];

        foreach ($defaultSequences as $sequence) {
            NumberSequence::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'code' => $sequence['code'],
                ],
                array_merge($sequence, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * Create default fiscal year and periods for a tenant.
     *
     * @param  Tenant  $tenant
     * @return void
     */
    protected function createDefaultFiscalYear(Tenant $tenant): void
    {
        $currentYear = now()->year;
        $startDate = now()->startOfYear()->toDateString();
        $endDate = now()->endOfYear()->toDateString();

        // Check if fiscal year already exists
        $existingYear = FiscalYear::where('tenant_id', $tenant->id)
            ->whereYear('start_date', $currentYear)
            ->first();

        if ($existingYear) {
            return;
        }

        // Create fiscal year
        $fiscalYear = FiscalYear::create([
            'tenant_id' => $tenant->id,
            'name' => "FY {$currentYear}",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
            'is_closed' => false,
        ]);

        // Create 12 monthly periods
        $months = [
            ['name' => 'January', 'code' => '01'],
            ['name' => 'February', 'code' => '02'],
            ['name' => 'March', 'code' => '03'],
            ['name' => 'April', 'code' => '04'],
            ['name' => 'May', 'code' => '05'],
            ['name' => 'June', 'code' => '06'],
            ['name' => 'July', 'code' => '07'],
            ['name' => 'August', 'code' => '08'],
            ['name' => 'September', 'code' => '09'],
            ['name' => 'October', 'code' => '10'],
            ['name' => 'November', 'code' => '11'],
            ['name' => 'December', 'code' => '12'],
        ];

        foreach ($months as $index => $month) {
            $periodStart = now()->setYear($currentYear)->setMonth($index + 1)->startOfMonth()->toDateString();
            $periodEnd = now()->setYear($currentYear)->setMonth($index + 1)->endOfMonth()->toDateString();

            FiscalPeriod::create([
                'tenant_id' => $tenant->id,
                'fiscal_year_id' => $fiscalYear->id,
                'name' => "{$month['name']} {$currentYear}",
                'code' => "{$currentYear}-{$month['code']}",
                'start_date' => $periodStart,
                'end_date' => $periodEnd,
                'period_number' => $index + 1,
                'is_active' => true,
                'is_closed' => false,
            ]);
        }
    }
}

