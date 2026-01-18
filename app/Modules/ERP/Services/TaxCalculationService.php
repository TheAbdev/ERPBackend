<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\TaxRate;

/**
 * Service for calculating taxes on invoices.
 */
class TaxCalculationService extends BaseService
{
    /**
     * Calculate tax for an invoice item.
     *
     * @param  float  $netAmount
     * @param  int|null  $taxRateId
     * @return array
     */
    public function calculateItemTax(float $netAmount, ?int $taxRateId = null): array
    {
        if (!$taxRateId) {
            return [
                'tax_rate_id' => null,
                'tax_rate_code' => null,
                'tax_rate_name' => null,
                'tax_rate' => 0,
                'net_amount' => $netAmount,
                'tax_amount' => 0,
                'gross_amount' => $netAmount,
            ];
        }

        $taxRate = TaxRate::where('tenant_id', $this->getTenantId())
            ->where('is_active', true)
            ->findOrFail($taxRateId);

        $taxAmount = $taxRate->calculateTax($netAmount);
        $grossAmount = $netAmount + $taxAmount;

        return [
            'tax_rate_id' => $taxRate->id,
            'tax_rate_code' => $taxRate->code,
            'tax_rate_name' => $taxRate->name,
            'tax_rate' => (float) $taxRate->rate,
            'net_amount' => round($netAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'gross_amount' => round($grossAmount, 2),
        ];
    }

    /**
     * Calculate and aggregate taxes for invoice items.
     *
     * @param  array  $items  Array of items with net_amount and tax_rate_id
     * @return array
     */
    public function calculateInvoiceTaxes(array $items): array
    {
        $taxBreakdown = [];
        $totalNet = 0;
        $totalTax = 0;
        $totalGross = 0;

        foreach ($items as $item) {
            $netAmount = $item['net_amount'] ?? ($item['quantity'] * $item['unit_price']);
            $taxRateId = $item['tax_rate_id'] ?? null;

            $taxCalculation = $this->calculateItemTax($netAmount, $taxRateId);

            // Store tax breakdown per item
            $item['tax_calculation'] = $taxCalculation;

            // Aggregate by tax rate
            if ($taxRateId) {
                $taxRateCode = $taxCalculation['tax_rate_code'];
                if (!isset($taxBreakdown[$taxRateCode])) {
                    $taxBreakdown[$taxRateCode] = [
                        'tax_rate_id' => $taxRateId,
                        'tax_rate_code' => $taxRateCode,
                        'tax_rate_name' => $taxCalculation['tax_rate_name'],
                        'tax_rate' => $taxCalculation['tax_rate'],
                        'net_amount' => 0,
                        'tax_amount' => 0,
                        'gross_amount' => 0,
                    ];
                }

                $taxBreakdown[$taxRateCode]['net_amount'] += $taxCalculation['net_amount'];
                $taxBreakdown[$taxRateCode]['tax_amount'] += $taxCalculation['tax_amount'];
                $taxBreakdown[$taxRateCode]['gross_amount'] += $taxCalculation['gross_amount'];
            } else {
                // No tax
                if (!isset($taxBreakdown['NO_TAX'])) {
                    $taxBreakdown['NO_TAX'] = [
                        'tax_rate_id' => null,
                        'tax_rate_code' => 'NO_TAX',
                        'tax_rate_name' => 'No Tax',
                        'tax_rate' => 0,
                        'net_amount' => 0,
                        'tax_amount' => 0,
                        'gross_amount' => 0,
                    ];
                }

                $taxBreakdown['NO_TAX']['net_amount'] += $taxCalculation['net_amount'];
                $taxBreakdown['NO_TAX']['gross_amount'] += $taxCalculation['gross_amount'];
            }

            $totalNet += $taxCalculation['net_amount'];
            $totalTax += $taxCalculation['tax_amount'];
            $totalGross += $taxCalculation['gross_amount'];
        }

        // Round totals
        $totalNet = round($totalNet, 2);
        $totalTax = round($totalTax, 2);
        $totalGross = round($totalGross, 2);

        // Round individual tax breakdown amounts
        foreach ($taxBreakdown as &$breakdown) {
            $breakdown['net_amount'] = round($breakdown['net_amount'], 2);
            $breakdown['tax_amount'] = round($breakdown['tax_amount'], 2);
            $breakdown['gross_amount'] = round($breakdown['gross_amount'], 2);
        }

        return [
            'items' => $items,
            'tax_breakdown' => array_values($taxBreakdown),
            'totals' => [
                'net_amount' => $totalNet,
                'tax_amount' => $totalTax,
                'gross_amount' => $totalGross,
            ],
        ];
    }

    /**
     * Get tax account for a tax rate.
     *
     * @param  int  $taxRateId
     * @return \App\Modules\ERP\Models\Account|null
     */
    public function getTaxAccount(int $taxRateId): ?\App\Modules\ERP\Models\Account
    {
        $taxRate = TaxRate::where('tenant_id', $this->getTenantId())
            ->findOrFail($taxRateId);

        return $taxRate->account;
    }
}

