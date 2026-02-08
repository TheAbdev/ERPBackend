<?php

namespace Database\Seeders;

use App\Modules\ERP\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable events during seeding to avoid audit logging issues
        Account::withoutEvents(function () {
            // Get all tenants (if any)
            $tenants = \App\Core\Models\Tenant::all();

            if ($tenants->isEmpty()) {
                $this->command->info('No tenants found. Creating accounts for tenant_id = 1');
                $this->createAccountsForTenant(1);
                return;
            }

            foreach ($tenants as $tenant) {
                $this->command->info("Creating accounts for tenant: {$tenant->name} (ID: {$tenant->id})");
                $this->createAccountsForTenant($tenant->id);
            }
        });
    }

    /**
     * Create accounts for a specific tenant
     */
    private function createAccountsForTenant($tenantId): void
    {
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
        $this->insertAccounts($tenantId, $accounts);
    }

    /**
     * Insert accounts recursively
     */
    private function insertAccounts($tenantId, $accounts, $parentId = null, $displayOrder = 0): void
    {
        foreach ($accounts as $accountData) {
            $children = $accountData['children'] ?? [];
            unset($accountData['children']);

            $displayOrder++;

            // Check if account already exists
            $existing = Account::where('tenant_id', $tenantId)
                ->where('code', $accountData['code'])
                ->first();

            if ($existing) {
                $account = $existing;
            } else {
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

                $this->command->line("Created account: {$accountData['code']} - {$accountData['name']}");
            }

            if (!empty($children)) {
                $this->insertAccounts($tenantId, $children, $account->id, 0);
            }
        }
    }
}



