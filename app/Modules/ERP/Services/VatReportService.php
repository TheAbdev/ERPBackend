<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\FiscalPeriod;
use App\Modules\ERP\Models\PurchaseInvoice;
use App\Modules\ERP\Models\SalesInvoice;

/**
 * Service for generating VAT Return reports.
 */
class VatReportService extends BaseService
{
    /**
     * Generate VAT Return report for a fiscal period.
     *
     * @param  int  $fiscalPeriodId
     * @return array
     */
    public function generateVatReturn(int $fiscalPeriodId): array
    {
        $fiscalPeriod = FiscalPeriod::where('tenant_id', $this->getTenantId())
            ->findOrFail($fiscalPeriodId);

        $fiscalYear = $fiscalPeriod->fiscalYear;

        // Get output VAT (from sales invoices)
        $outputVat = $this->calculateOutputVat($fiscalPeriod);

        // Get input VAT (from purchase invoices)
        $inputVat = $this->calculateInputVat($fiscalPeriod);

        // Calculate net VAT
        $netVat = $outputVat['total_vat'] - $inputVat['total_vat'];
        $isPayable = $netVat >= 0;

        return [
            'fiscal_period' => [
                'id' => $fiscalPeriod->id,
                'name' => $fiscalPeriod->name,
                'code' => $fiscalPeriod->code,
                'start_date' => $fiscalPeriod->start_date->format('Y-m-d'),
                'end_date' => $fiscalPeriod->end_date->format('Y-m-d'),
            ],
            'fiscal_year' => [
                'id' => $fiscalYear->id,
                'name' => $fiscalYear->name,
            ],
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'net_vat' => [
                'amount' => (float) abs($netVat),
                'is_payable' => $isPayable,
                'is_refundable' => !$isPayable,
            ],
        ];
    }

    /**
     * Calculate output VAT from sales invoices.
     *
     * @param  \App\Modules\ERP\Models\FiscalPeriod  $fiscalPeriod
     * @return array
     */
    protected function calculateOutputVat(FiscalPeriod $fiscalPeriod): array
    {
        $invoices = SalesInvoice::where('tenant_id', $this->getTenantId())
            ->where('fiscal_period_id', $fiscalPeriod->id)
            ->where('status', 'issued')
            ->with(['items.taxRate'])
            ->get();

        $vatByRate = [];
        $totalNet = 0;
        $totalVat = 0;
        $totalGross = 0;
        $invoiceDetails = [];

        foreach ($invoices as $invoice) {
            $invoiceNet = $invoice->net_amount ?? ($invoice->total - $invoice->tax_amount);
            $invoiceVat = $invoice->tax_amount ?? 0;
            $invoiceGross = $invoice->total;

            $totalNet += $invoiceNet;
            $totalVat += $invoiceVat;
            $totalGross += $invoiceGross;

            // Process tax breakdown
            if ($invoice->tax_breakdown) {
                foreach ($invoice->tax_breakdown as $taxLine) {
                    $taxRateCode = $taxLine['tax_rate_code'] ?? 'NO_TAX';
                    if (!isset($vatByRate[$taxRateCode])) {
                        $vatByRate[$taxRateCode] = [
                            'tax_rate_code' => $taxRateCode,
                            'tax_rate_name' => $taxLine['tax_rate_name'] ?? 'No Tax',
                            'tax_rate' => $taxLine['tax_rate'] ?? 0,
                            'net_amount' => 0,
                            'vat_amount' => 0,
                            'gross_amount' => 0,
                        ];
                    }

                    $vatByRate[$taxRateCode]['net_amount'] += $taxLine['net_amount'] ?? 0;
                    $vatByRate[$taxRateCode]['vat_amount'] += $taxLine['tax_amount'] ?? 0;
                    $vatByRate[$taxRateCode]['gross_amount'] += $taxLine['gross_amount'] ?? 0;
                }
            }

            if ($invoiceVat > 0) {
                $invoiceDetails[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->issue_date->format('Y-m-d'),
                    'customer_name' => $invoice->customer_name,
                    'net_amount' => (float) $invoiceNet,
                    'vat_amount' => (float) $invoiceVat,
                    'gross_amount' => (float) $invoiceGross,
                ];
            }
        }

        // Round VAT breakdown amounts
        foreach ($vatByRate as &$rate) {
            $rate['net_amount'] = round($rate['net_amount'], 2);
            $rate['vat_amount'] = round($rate['vat_amount'], 2);
            $rate['gross_amount'] = round($rate['gross_amount'], 2);
        }

        return [
            'vat_by_rate' => array_values($vatByRate),
            'totals' => [
                'net_amount' => round($totalNet, 2),
                'vat_amount' => round($totalVat, 2),
                'gross_amount' => round($totalGross, 2),
            ],
            'total_vat' => round($totalVat, 2),
            'invoice_count' => count($invoiceDetails),
            'invoices' => $invoiceDetails,
        ];
    }

    /**
     * Calculate input VAT from purchase invoices.
     *
     * @param  \App\Modules\ERP\Models\FiscalPeriod  $fiscalPeriod
     * @return array
     */
    protected function calculateInputVat(FiscalPeriod $fiscalPeriod): array
    {
        $invoices = PurchaseInvoice::where('tenant_id', $this->getTenantId())
            ->where('fiscal_period_id', $fiscalPeriod->id)
            ->where('status', 'issued')
            ->with(['items.taxRate'])
            ->get();

        $vatByRate = [];
        $totalNet = 0;
        $totalVat = 0;
        $totalGross = 0;
        $invoiceDetails = [];

        foreach ($invoices as $invoice) {
            $invoiceNet = $invoice->net_amount ?? ($invoice->total - $invoice->tax_amount);
            $invoiceVat = $invoice->tax_amount ?? 0;
            $invoiceGross = $invoice->total;

            $totalNet += $invoiceNet;
            $totalVat += $invoiceVat;
            $totalGross += $invoiceGross;

            // Process tax breakdown
            if ($invoice->tax_breakdown) {
                foreach ($invoice->tax_breakdown as $taxLine) {
                    $taxRateCode = $taxLine['tax_rate_code'] ?? 'NO_TAX';
                    if (!isset($vatByRate[$taxRateCode])) {
                        $vatByRate[$taxRateCode] = [
                            'tax_rate_code' => $taxRateCode,
                            'tax_rate_name' => $taxLine['tax_rate_name'] ?? 'No Tax',
                            'tax_rate' => $taxLine['tax_rate'] ?? 0,
                            'net_amount' => 0,
                            'vat_amount' => 0,
                            'gross_amount' => 0,
                        ];
                    }

                    $vatByRate[$taxRateCode]['net_amount'] += $taxLine['net_amount'] ?? 0;
                    $vatByRate[$taxRateCode]['vat_amount'] += $taxLine['tax_amount'] ?? 0;
                    $vatByRate[$taxRateCode]['gross_amount'] += $taxLine['gross_amount'] ?? 0;
                }
            }

            if ($invoiceVat > 0) {
                $invoiceDetails[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->issue_date->format('Y-m-d'),
                    'supplier_name' => $invoice->supplier_name,
                    'net_amount' => (float) $invoiceNet,
                    'vat_amount' => (float) $invoiceVat,
                    'gross_amount' => (float) $invoiceGross,
                ];
            }
        }

        // Round VAT breakdown amounts
        foreach ($vatByRate as &$rate) {
            $rate['net_amount'] = round($rate['net_amount'], 2);
            $rate['vat_amount'] = round($rate['vat_amount'], 2);
            $rate['gross_amount'] = round($rate['gross_amount'], 2);
        }

        return [
            'vat_by_rate' => array_values($vatByRate),
            'totals' => [
                'net_amount' => round($totalNet, 2),
                'vat_amount' => round($totalVat, 2),
                'gross_amount' => round($totalGross, 2),
            ],
            'total_vat' => round($totalVat, 2),
            'invoice_count' => count($invoiceDetails),
            'invoices' => $invoiceDetails,
        ];
    }
}

