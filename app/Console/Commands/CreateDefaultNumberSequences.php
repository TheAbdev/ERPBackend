<?php

namespace App\Console\Commands;

use App\Core\Models\Tenant;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\FiscalYear;
use App\Modules\ERP\Models\NumberSequence;
use Illuminate\Console\Command;

class CreateDefaultNumberSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'number-sequences:create-default {--tenant-id= : Create sequences for a specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default number sequences for all tenants or a specific tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant-id');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return 1;
            }
            $this->createSequencesForTenant($tenant);
            $this->createFiscalYearForTenant($tenant);
        } else {
            $tenants = Tenant::all();
            $this->info("Creating default number sequences and fiscal years for {$tenants->count()} tenant(s)...");
            
            foreach ($tenants as $tenant) {
                $this->createSequencesForTenant($tenant);
                $this->createFiscalYearForTenant($tenant);
            }
            
            $this->info('✅ Default number sequences and fiscal years created successfully!');
        }

        return 0;
    }

    /**
     * Create default number sequences for a tenant.
     *
     * @param  Tenant  $tenant
     * @return void
     */
    protected function createSequencesForTenant(Tenant $tenant): void
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

        $created = 0;
        $skipped = 0;

        foreach ($defaultSequences as $sequence) {
            $existing = NumberSequence::where('tenant_id', $tenant->id)
                ->where('code', $sequence['code'])
                ->first();

            if ($existing) {
                $skipped++;
                $this->line("  ⏭️  Skipped '{$sequence['code']}' for tenant '{$tenant->name}' (already exists)");
            } else {
                NumberSequence::create(array_merge($sequence, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]));
                $created++;
                $this->line("  ✅ Created '{$sequence['code']}' for tenant '{$tenant->name}'");
            }
        }

        if ($created > 0 || $skipped > 0) {
            $this->info("  Tenant '{$tenant->name}' (ID: {$tenant->id}): {$created} created, {$skipped} skipped");
        }
    }

    /**
     * Create default fiscal year and periods for a tenant.
     *
     * @param  Tenant  $tenant
     * @return void
     */
    protected function createFiscalYearForTenant(Tenant $tenant): void
    {
        $currentYear = now()->year;
        $startDate = now()->startOfYear()->toDateString();
        $endDate = now()->endOfYear()->toDateString();

        // Check if fiscal year already exists
        $existingYear = FiscalYear::where('tenant_id', $tenant->id)
            ->whereYear('start_date', $currentYear)
            ->first();

        if ($existingYear) {
            $this->line("  ⏭️  Skipped fiscal year for tenant '{$tenant->name}' (already exists)");
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

        $this->line("  ✅ Created fiscal year 'FY {$currentYear}' for tenant '{$tenant->name}'");

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

        $periodsCreated = 0;
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
            $periodsCreated++;
        }

        $this->line("  ✅ Created {$periodsCreated} fiscal periods for tenant '{$tenant->name}'");
    }
}
