<?php

namespace App\Platform\Services;

use App\Core\Models\Permission;
use App\Core\Models\Role;
use App\Core\Models\Tenant;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use App\Modules\ERP\Models\Account;
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

            // Create default chart of accounts for the tenant
            $this->createDefaultChartOfAccounts($tenant);

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
            if (array_key_exists('settings', $data)) {
                $this->syncTenantOwnerPermissions($tenant->fresh());
            }
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

            $this->syncTenantOwnerPermissions($tenant, $superAdminRole);

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
     * Sync tenant owner role permissions based on tenant module settings.
     *
     * @param  Tenant  $tenant
     * @param  Role|null  $role
     * @return void
     */
    protected function syncTenantOwnerPermissions(Tenant $tenant, ?Role $role = null): void
    {
        $superAdminRole = $role ?: Role::where('tenant_id', $tenant->id)
            ->where('slug', 'super_admin')
            ->first();

        if (! $superAdminRole) {
            return;
        }

        $permissionIds = $this->resolveTenantOwnerPermissionIds($tenant);
        $superAdminRole->permissions()->sync($permissionIds);
    }

    /**
     * Resolve permission IDs for tenant owner based on module selections.
     *
     * @param  Tenant  $tenant
     * @return array<int, int>
     */
    protected function resolveTenantOwnerPermissionIds(Tenant $tenant): array
    {
        $modules = (array) data_get($tenant->settings ?? [], 'modules', []);
        $erpEnabled = (bool) data_get($modules, 'erp', false);
        $crmEnabled = (bool) data_get($modules, 'crm', false);
        $hrEnabled = (bool) data_get($modules, 'hr', false);

        $permissionIds = collect();

        // System permissions are always available (exclude platform and tenant management).
        $permissionIds = $permissionIds->merge(
            Permission::where('slug', 'like', 'core.%')
                ->where('slug', 'not like', 'core.tenants.%')
                ->pluck('id')
        );

        if ($erpEnabled) {
            $permissionIds = $permissionIds->merge(
                $this->getPermissionIdsByPrefixes([
                    'erp.',
                    'ecommerce.',
                    'crm.contacts.',
                ])
            );
        }

        if ($crmEnabled) {
            $permissionIds = $permissionIds->merge(
                $this->getPermissionIdsByPrefixes([
                    'crm.',
                ])
            );
        }

        if ($hrEnabled) {
            $permissionIds = $permissionIds->merge(
                $this->getPermissionIdsByPrefixes([
                    'hr.',
                    'erp.accounting.journals.',
                    'erp.fiscal-years.',
                    'erp.fiscal-periods.',
                ])
            );
        }

        return $permissionIds->unique()->values()->all();
    }

    /**
     * Get permission IDs by slug prefixes.
     *
     * @param  array<int, string>  $prefixes
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function getPermissionIdsByPrefixes(array $prefixes)
    {
        return Permission::where(function ($query) use ($prefixes) {
            foreach ($prefixes as $prefix) {
                $query->orWhere('slug', 'like', $prefix . '%');
            }
        })->pluck('id');
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

    /**
     * Create default chart of accounts for a tenant.
     *
     * @param  Tenant  $tenant
     * @return void
     */
    protected function createDefaultChartOfAccounts(Tenant $tenant): void
    {
        // Disable events to prevent audit logging during seeding
        Account::withoutEvents(function () use ($tenant) {
            // Define the chart of accounts structure
            $accounts = [
                // Assets
                [
                    'code' => 'A',
                    'name' => 'Assets',
                    'type' => 'asset',
                    'description' => 'Asset accounts',
                    'children' => [
                        [
                            'code' => 'A-1',
                            'name' => 'Current Assets',
                            'type' => 'asset',
                            'description' => 'Current assets',
                            'children' => [
                                [
                                    'code' => 'AR',
                                    'name' => 'Accounts Receivable',
                                    'type' => 'asset',
                                    'description' => 'Money owed by customers',
                                ],
                                [
                                    'code' => 'INV',
                                    'name' => 'Inventory',
                                    'type' => 'asset',
                                    'description' => 'Products held for sale',
                                ],
                                [
                                    'code' => 'CASH',
                                    'name' => 'Cash',
                                    'type' => 'asset',
                                    'description' => 'Cash and cash equivalents',
                                ],
                            ],
                        ],
                        [
                            'code' => 'A-2',
                            'name' => 'Fixed Assets',
                            'type' => 'asset',
                            'description' => 'Long-term assets',
                            'children' => [
                                [
                                    'code' => 'PPE',
                                    'name' => 'Property, Plant & Equipment',
                                    'type' => 'asset',
                                    'description' => 'Fixed assets',
                                ],
                            ],
                        ],
                    ],
                ],
                // Liabilities
                [
                    'code' => 'L',
                    'name' => 'Liabilities',
                    'type' => 'liability',
                    'description' => 'Liability accounts',
                    'children' => [
                        [
                            'code' => 'L-1',
                            'name' => 'Current Liabilities',
                            'type' => 'liability',
                            'description' => 'Short-term liabilities',
                            'children' => [
                                [
                                    'code' => 'AP',
                                    'name' => 'Accounts Payable',
                                    'type' => 'liability',
                                    'description' => 'Money owed to suppliers',
                                ],
                                [
                                    'code' => 'TAX',
                                    'name' => 'VAT Payable',
                                    'type' => 'liability',
                                    'description' => 'VAT collected and payable',
                                ],
                            ],
                        ],
                    ],
                ],
                // Equity
                [
                    'code' => 'E',
                    'name' => 'Equity',
                    'type' => 'equity',
                    'description' => 'Equity accounts',
                    'children' => [
                        [
                            'code' => 'CAPITAL',
                            'name' => 'Capital Stock',
                            'type' => 'equity',
                            'description' => 'Owner capital',
                        ],
                    ],
                ],
                // Revenue
                [
                    'code' => 'R',
                    'name' => 'Revenue',
                    'type' => 'revenue',
                    'description' => 'Revenue accounts',
                    'children' => [
                        [
                            'code' => 'REV',
                            'name' => 'Sales Revenue',
                            'type' => 'revenue',
                            'description' => 'Revenue from sales',
                        ],
                        [
                            'code' => 'SRV-REV',
                            'name' => 'Service Revenue',
                            'type' => 'revenue',
                            'description' => 'Revenue from services',
                        ],
                    ],
                ],
                // Expenses
                [
                    'code' => 'EX',
                    'name' => 'Expenses',
                    'type' => 'expense',
                    'description' => 'Expense accounts',
                    'children' => [
                        [
                            'code' => 'COGS',
                            'name' => 'Cost of Goods Sold',
                            'type' => 'expense',
                            'description' => 'Cost of products sold',
                        ],
                        [
                            'code' => 'PUR',
                            'name' => 'Purchase Expense',
                            'type' => 'expense',
                            'description' => 'Purchase of goods',
                        ],
                        [
                            'code' => 'SALARY',
                            'name' => 'Salary Expense',
                            'type' => 'expense',
                            'description' => 'Employee salaries',
                        ],
                        [
                            'code' => 'RENT',
                            'name' => 'Rent Expense',
                            'type' => 'expense',
                            'description' => 'Building rent',
                        ],
                    ],
                ],
            ];

            // Insert accounts recursively
            $this->insertAccountsForTenant($tenant->id, $accounts);
        });
    }

    /**
     * Insert accounts recursively for a tenant.
     *
     * @param  int  $tenantId
     * @param  array  $accounts
     * @param  int|null  $parentId
     * @param  int  $displayOrder
     * @return void
     */
    private function insertAccountsForTenant($tenantId, $accounts, $parentId = null, $displayOrder = 0): void
    {
        foreach ($accounts as $accountData) {
            $children = $accountData['children'] ?? [];
            unset($accountData['children']);

            $displayOrder++;

            // Check if account already exists
            $existing = Account::where('tenant_id', $tenantId)
                ->where('code', $accountData['code'])
                ->first();

            if (!$existing) {
                $account = Account::create([
                    'tenant_id' => $tenantId,
                    'parent_id' => $parentId,
                    'code' => $accountData['code'],
                    'name' => $accountData['name'],
                    'type' => $accountData['type'],
                    'description' => $accountData['description'] ?? null,
                    'is_active' => true,
                    'display_order' => $displayOrder,
                ]);
            } else {
                $account = $existing;
            }

            if (!empty($children)) {
                $this->insertAccountsForTenant($tenantId, $children, $account->id, 0);
            }
        }
    }
}
