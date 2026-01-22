<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Report;
use App\Modules\ERP\Models\SalesOrder;
use App\Modules\ERP\Models\SalesInvoice;
use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\FixedAsset;
use App\Modules\ERP\Models\Expense;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\Contact;
use Illuminate\Support\Facades\Cache;

/**
 * Service for generating reports and dashboard metrics.
 */
class ReportService extends BaseService
{
    protected TrialBalanceService $trialBalanceService;
    protected ProfitLossService $profitLossService;
    protected BalanceSheetService $balanceSheetService;
    protected VatReportService $vatReportService;
    protected AssetReportService $assetReportService;

    public function __construct(
        TenantContext $tenantContext,
        TrialBalanceService $trialBalanceService,
        ProfitLossService $profitLossService,
        BalanceSheetService $balanceSheetService,
        VatReportService $vatReportService,
        AssetReportService $assetReportService
    ) {
        parent::__construct($tenantContext);
        $this->trialBalanceService = $trialBalanceService;
        $this->profitLossService = $profitLossService;
        $this->balanceSheetService = $balanceSheetService;
        $this->vatReportService = $vatReportService;
        $this->assetReportService = $assetReportService;
    }

    /**
     * Generate report data.
     *
     * @param  int  $reportId
     * @param  array|null  $filters
     * @return array
     */
    public function generateReport(int $reportId, ?array $filters = null): array
    {
        $report = Report::where('tenant_id', $this->getTenantId())
            ->findOrFail($reportId);

        $cacheKey = "report_{$reportId}_" . md5(json_encode($filters ?? $report->filters));

        return Cache::remember($cacheKey, 3600, function () use ($report, $filters) {
            $effectiveFilters = $filters ?? $report->filters ?? [];

            return match ($report->type) {
                'trial_balance' => $this->trialBalanceService->generateTrialBalance(
                    $effectiveFilters['fiscal_period_id'] ?? null
                ),
                'profit_loss' => $this->profitLossService->generateProfitLoss(
                    $effectiveFilters['fiscal_period_id'] ?? null
                ),
                'balance_sheet' => $this->balanceSheetService->generateBalanceSheet(
                    $effectiveFilters['fiscal_period_id'] ?? null
                ),
                'vat_return' => $this->vatReportService->generateVatReturn(
                    $effectiveFilters['fiscal_period_id'] ?? null
                ),
                'asset_register' => $this->assetReportService->generateAssetRegister(
                    $effectiveFilters['fiscal_period_id'] ?? null
                ),
                default => ['error' => 'Unknown report type'],
            };
        });
    }

    /**
     * Generate dashboard metrics.
     *
     * @param  int|null  $tenantId
     * @param  int|null  $userId
     * @return array
     */
    public function generateDashboardMetrics(?int $tenantId = null, ?int $userId = null): array
    {
        $tenantId = $tenantId ?? $this->getTenantId();
        $cacheKey = $userId 
            ? "dashboard_metrics_{$tenantId}_user_{$userId}"
            : "dashboard_metrics_{$tenantId}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $userId) {
            \Illuminate\Support\Facades\Log::info('Generating dashboard metrics', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]);

            $erpMetrics = $this->getErpMetrics($tenantId, $userId);
            $crmMetrics = $this->getCrmMetrics($tenantId, $userId);
            $financialMetrics = $this->getFinancialMetrics($tenantId, $userId);

            \Illuminate\Support\Facades\Log::info('Dashboard metrics calculated', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'erp' => $erpMetrics,
                'crm' => $crmMetrics,
                'financial' => $financialMetrics,
            ]);

            return [
                'erp' => $erpMetrics,
                'crm' => $crmMetrics,
                'financial' => $financialMetrics,
            ];
        });
    }

    /**
     * Get ERP metrics.
     *
     * @param  int  $tenantId
     * @param  int|null  $userId
     * @return array
     */
    protected function getErpMetrics(int $tenantId, ?int $userId = null): array
    {
        // Base query for ERP models that only have 'created_by' column
        $baseQuery = function ($model) use ($tenantId, $userId) {
            $query = $model::where('tenant_id', $tenantId);
            if ($userId) {
                $query->where('created_by', $userId);
            }
            return $query;
        };

        // Calculate total sales from sales orders
        $totalSales = (clone $baseQuery(SalesOrder::class))
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount') ?? 0;

        // Get pending orders count
        $pendingOrders = (clone $baseQuery(SalesOrder::class))
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->count();

        // Get completed orders count
        $completedOrders = (clone $baseQuery(SalesOrder::class))
            ->where('status', 'completed')
            ->count();

        return [
            // Products don't have created_by, so show all tenant products regardless of user
            'total_products' => Product::where('tenant_id', $tenantId)->count(),
            'total_sales' => (float) $totalSales,
            'pending_orders' => $pendingOrders,
            'completed_orders' => $completedOrders,
            'total_invoices' => (clone $baseQuery(SalesInvoice::class))
                ->where('status', 'issued')->count(),
            'pending_invoices' => (clone $baseQuery(SalesInvoice::class))
                ->where('status', 'draft')->count(),
            'total_assets' => $userId
                ? FixedAsset::where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->where('created_by', $userId)->count()
                : FixedAsset::where('tenant_id', $tenantId)
                    ->where('status', 'active')->count(),
        ];
    }

    /**
     * Get CRM metrics.
     *
     * @param  int  $tenantId
     * @param  int|null  $userId
     * @return array
     */
    protected function getCrmMetrics(int $tenantId, ?int $userId = null): array
    {
        // Base query for CRM models that have both created_by and assigned_to (Lead, Deal)
        $baseQuery = function ($model) use ($tenantId, $userId) {
            $query = $model::where('tenant_id', $tenantId);
            if ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('created_by', $userId)
                      ->orWhere('assigned_to', $userId);
                });
            }
            return $query;
        };

        // Base query for CRM models that only have created_by (Contact)
        $baseQueryCreatedBy = function ($model) use ($tenantId, $userId) {
            $query = $model::where('tenant_id', $tenantId);
            if ($userId) {
                $query->where('created_by', $userId);
            }
            return $query;
        };

        // Calculate won deals value (using 'amount' column, not 'value')
        $wonDealsValue = (clone $baseQuery(Deal::class))
            ->where('status', 'won')
            ->sum('amount') ?? 0;

        // Calculate open deals value (using 'amount' column, not 'value')
        $openDealsValue = (clone $baseQuery(Deal::class))
            ->whereIn('status', ['open', 'negotiation', 'proposal'])
            ->sum('amount') ?? 0;

        return [
            'total_leads' => $baseQuery(Lead::class)->count(),
            'total_contacts' => $baseQueryCreatedBy(Contact::class)->count(),
            'total_deals' => $baseQuery(Deal::class)->count(),
            'won_deals_value' => (float) $wonDealsValue,
            'open_deals_value' => (float) $openDealsValue,
            'won_deals' => (clone $baseQuery(Deal::class))
                ->where('status', 'won')->count(),
        ];
    }

    /**
     * Get financial metrics.
     *
     * @param  int  $tenantId
     * @param  int|null  $userId
     * @return array
     */
    protected function getFinancialMetrics(int $tenantId, ?int $userId = null): array
    {
        try {
            $fiscalPeriod = \App\Modules\ERP\Services\AccountingService::class;
            $accountingService = app($fiscalPeriod);
            $activePeriod = $accountingService->getActiveFiscalPeriod(now());

            $totalRevenue = $this->getTotalRevenue($tenantId, $activePeriod->id);
            $totalExpenses = $this->getTotalExpenses($tenantId, $activePeriod->id);
            $netProfit = $totalRevenue - $totalExpenses;

            return [
                'fiscal_period' => [
                    'id' => $activePeriod->id,
                    'name' => $activePeriod->name,
                ],
                'total_revenue' => (float) $totalRevenue,
                'total_expenses' => (float) $totalExpenses,
                'net_profit' => (float) $netProfit,
            ];
        } catch (\Exception $e) {
            // Fallback: calculate from sales invoices and expenses
            $totalRevenue = SalesInvoice::where('tenant_id', $tenantId)
                ->where('status', 'issued')
                ->sum('total') ?? 0;

            $totalExpenses = Expense::where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->sum('amount') ?? 0;

            $netProfit = $totalRevenue - $totalExpenses;

            return [
                'fiscal_period' => null,
                'total_revenue' => (float) $totalRevenue,
                'total_expenses' => (float) $totalExpenses,
                'net_profit' => (float) $netProfit,
            ];
        }
    }

    /**
     * Get total revenue for period.
     *
     * @param  int  $tenantId
     * @param  int  $fiscalPeriodId
     * @return float
     */
    protected function getTotalRevenue(int $tenantId, int $fiscalPeriodId): float
    {
        return (float) JournalEntryLine::whereHas('journalEntry', function ($query) use ($tenantId, $fiscalPeriodId) {
            $query->where('tenant_id', $tenantId)
                ->where('fiscal_period_id', $fiscalPeriodId)
                ->where('status', 'posted');
        })
        ->whereHas('account', function ($query) {
            $query->where('type', 'revenue');
        })
        ->sum('credit');
    }

    /**
     * Get total expenses for period.
     *
     * @param  int  $tenantId
     * @param  int  $fiscalPeriodId
     * @return float
     */
    protected function getTotalExpenses(int $tenantId, int $fiscalPeriodId): float
    {
        return (float) JournalEntryLine::whereHas('journalEntry', function ($query) use ($tenantId, $fiscalPeriodId) {
            $query->where('tenant_id', $tenantId)
                ->where('fiscal_period_id', $fiscalPeriodId)
                ->where('status', 'posted');
        })
        ->whereHas('account', function ($query) {
            $query->where('type', 'expense');
        })
        ->sum('debit');
    }

    /**
     * Export report.
     *
     * @param  int  $reportId
     * @param  string  $format
     * @param  array|null  $filters
     * @return string|mixed
     */
    public function exportReport(int $reportId, string $format = 'csv', ?array $filters = null)
    {
        $data = $this->generateReport($reportId, $filters);

        return match ($format) {
            'csv' => $this->exportToCsv($data),
            'excel' => $this->exportToExcel($data),
            'pdf' => $this->exportToPdf($data),
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            default => json_encode($data, JSON_PRETTY_PRINT),
        };
    }

    /**
     * Export data to CSV.
     *
     * @param  array  $data
     * @return string
     */
    protected function exportToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Flatten data for CSV
        if (isset($data['accounts']) && is_array($data['accounts'])) {
            // For trial balance, profit/loss, etc.
            fputcsv($output, ['Account', 'Debit', 'Credit', 'Balance']);
            foreach ($data['accounts'] as $account) {
                fputcsv($output, [
                    $account['name'] ?? '',
                    $account['debit'] ?? 0,
                    $account['credit'] ?? 0,
                    $account['balance'] ?? 0,
                ]);
            }
        } else {
            // Generic export
            fputcsv($output, array_keys($data));
            fputcsv($output, array_values($data));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export data to Excel (requires maatwebsite/excel).
     *
     * @param  array  $data
     * @return string|mixed
     */
    protected function exportToExcel(array $data)
    {
        // Check if Excel package is installed
        if (!class_exists('Maatwebsite\Excel\Facades\Excel')) {
            throw new \Exception('Excel export requires maatwebsite/excel package. Run: composer require maatwebsite/excel');
        }

        // Create export class dynamically or use inline
        // For now, return CSV as fallback
        return $this->exportToCsv($data);
    }

    /**
     * Export data to PDF (requires PDF library).
     *
     * @param  array  $data
     * @return string
     */
    protected function exportToPdf(array $data): string
    {
        // Check if PDF package is installed
        $hasDomPdf = class_exists('Barryvdh\DomPDF\Facade\Pdf');
        $hasSpatiePdf = class_exists('Spatie\LaravelPdf\Facades\Pdf');

        if (!$hasDomPdf && !$hasSpatiePdf) {
            throw new \Exception('PDF export requires a PDF library. Run: composer require barryvdh/laravel-dompdf OR composer require spatie/laravel-pdf');
        }

        // Generate PDF
        // This is a placeholder - implement based on chosen PDF library
        // For now, return JSON as fallback until view template is created
        if ($hasDomPdf) {
            try {
                $html = '<html><body><pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre></body></html>';
                $pdf = app('dompdf.wrapper');
                return $pdf->loadHTML($html)->output();
            } catch (\Exception $e) {
                // Fallback to JSON if PDF generation fails
                return json_encode($data, JSON_PRETTY_PRINT);
            }
        }

        // Fallback to JSON
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

