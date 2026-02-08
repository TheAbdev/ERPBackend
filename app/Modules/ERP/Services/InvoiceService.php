<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\ERP\Models\PurchaseInvoice;
use App\Modules\ERP\Models\SalesInvoice;
use App\Modules\ERP\Services\TaxCalculationService;
use App\Modules\ERP\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling invoice operations.
 */
class InvoiceService extends BaseService
{
    protected AccountingService $accountingService;
    protected TaxCalculationService $taxCalculationService;
    protected WorkflowService $workflowService;

    public function __construct(
        TenantContext $tenantContext,
        AccountingService $accountingService,
        TaxCalculationService $taxCalculationService,
        WorkflowService $workflowService
    ) {
        parent::__construct($tenantContext);
        $this->accountingService = $accountingService;
        $this->taxCalculationService = $taxCalculationService;
        $this->workflowService = $workflowService;
    }

    /**
     * Issue a sales invoice.
     *
     * @param  \App\Modules\ERP\Models\SalesInvoice  $invoice
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry|null
     *
     * @throws \Exception
     */
    public function issueInvoice(SalesInvoice $invoice, int $userId): ?JournalEntry
    {
        if (!$invoice->isDraft()) {
            throw new \Exception('Only draft invoices can be issued.');
        }

        // Check if workflow approval is required
        if ($this->workflowService->requiresApproval($invoice)) {
            // Start workflow instead of issuing directly
            $this->workflowService->startWorkflow($invoice, $userId);

            // Update invoice status to pending_approval
            $invoice->update(['status' => 'pending_approval']);

            throw new \Exception('Invoice requires approval. Workflow has been initiated.');
        }

        // Validate fiscal period
        $this->accountingService->validateFiscalPeriod($invoice->fiscal_period_id);

        // Get accounts
        $accountsReceivableAccount = $this->getAccountByCode('AR');
        $salesRevenueAccount = $this->getAccountByCode('REV');

        if (!$accountsReceivableAccount || !$salesRevenueAccount) {
            throw new \Exception('Required accounts (AR, REV) not configured.');
        }

        return DB::transaction(function () use ($invoice, $userId, $accountsReceivableAccount, $salesRevenueAccount) {
            // Update invoice status
            $invoice->update([
                'status' => 'issued',
                'issued_by' => $userId,
                'issued_at' => now(),
            ]);

            // Create journal entry
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($invoice->issue_date);
            $fiscalYear = $fiscalPeriod->fiscalYear;

            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $invoice->issue_date,
                'reference_type' => SalesInvoice::class,
                'reference_id' => $invoice->id,
                'description' => "Sales Invoice: {$invoice->invoice_number}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $invoice->currency;
            $taxAmount = (float) ($invoice->tax_amount ?? 0);
            $grossAmount = (float) $invoice->total;
            // Net amount is always gross - tax
            $netAmount = $grossAmount - $taxAmount;

            $lineNumber = 1;

            // Debit: Accounts Receivable (Gross amount)
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $accountsReceivableAccount->id,
                'currency_id' => $currency->id,
                'debit' => $grossAmount,
                'credit' => 0,
                'amount_base' => $grossAmount,
                'description' => "Invoice {$invoice->invoice_number}",
                'line_number' => $lineNumber++,
            ]);

            // Credit: Sales Revenue (Net amount = total - tax)
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $salesRevenueAccount->id,
                'currency_id' => $currency->id,
                'debit' => 0,
                'credit' => $netAmount,
                'amount_base' => $netAmount,
                'description' => "Sales Revenue for Invoice {$invoice->invoice_number}",
                'line_number' => $lineNumber++,
            ]);

            // Credit: VAT Payable (if tax exists)
            if ($taxAmount > 0.01) {
                // If tax_breakdown exists, use it; otherwise create a single entry
                if ($invoice->tax_breakdown && !empty($invoice->tax_breakdown)) {
                    $totalTaxFromBreakdown = 0;
                    foreach ($invoice->tax_breakdown as $taxLine) {
                        $taxRateId = $taxLine['tax_rate_id'] ?? null;
                        $lineTaxAmount = (float) ($taxLine['tax_amount'] ?? 0);
                        if ($lineTaxAmount > 0) {
                            if ($taxRateId) {
                                $taxAccount = $this->taxCalculationService->getTaxAccount($taxRateId);
                                if ($taxAccount) {
                                    JournalEntryLine::create([
                                        'tenant_id' => $this->getTenantId(),
                                        'journal_entry_id' => $entry->id,
                                        'account_id' => $taxAccount->id,
                                        'currency_id' => $currency->id,
                                        'debit' => 0,
                                        'credit' => $lineTaxAmount,
                                        'amount_base' => $lineTaxAmount,
                                        'description' => "VAT Payable ({$taxLine['tax_rate_code']}) for Invoice {$invoice->invoice_number}",
                                        'line_number' => $lineNumber++,
                                    ]);
                                    $totalTaxFromBreakdown += $lineTaxAmount;
                                }
                            }
                        }
                    }
                    // If total tax from breakdown doesn't match, add difference
                    $diff = abs($taxAmount - $totalTaxFromBreakdown);
                    if ($diff > 0.01) {
                        $taxAccount = $this->getAccountByCode('TAX');
                        if (!$taxAccount) {
                            $taxRate = \App\Modules\ERP\Models\TaxRate::where('tenant_id', $this->getTenantId())->first();
                            if ($taxRate) {
                                $taxAccount = $this->taxCalculationService->getTaxAccount($taxRate->id);
                            }
                        }
                        if ($taxAccount) {
                            JournalEntryLine::create([
                                'tenant_id' => $this->getTenantId(),
                                'journal_entry_id' => $entry->id,
                                'account_id' => $taxAccount->id,
                                'currency_id' => $currency->id,
                                'debit' => 0,
                                'credit' => $diff,
                                'amount_base' => $diff,
                                'description' => "VAT Payable (Adjustment) for Invoice {$invoice->invoice_number}",
                                'line_number' => $lineNumber++,
                            ]);
                        }
                    }
                } else {
                    // If no tax breakdown, create a single VAT Payable entry
                    $taxAccount = $this->getAccountByCode('TAX');
                    if (!$taxAccount) {
                        $taxRate = \App\Modules\ERP\Models\TaxRate::where('tenant_id', $this->getTenantId())->first();
                        if ($taxRate) {
                            $taxAccount = $this->taxCalculationService->getTaxAccount($taxRate->id);
                        }
                    }

                    if ($taxAccount) {
                        JournalEntryLine::create([
                            'tenant_id' => $this->getTenantId(),
                            'journal_entry_id' => $entry->id,
                            'account_id' => $taxAccount->id,
                            'currency_id' => $currency->id,
                            'debit' => 0,
                            'credit' => $taxAmount,
                            'amount_base' => $taxAmount,
                            'description' => "VAT Payable for Invoice {$invoice->invoice_number}",
                            'line_number' => $lineNumber++,
                        ]);
                    }
                }
            }

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }

    /**
     * Issue a purchase invoice.
     *
     * @param  \App\Modules\ERP\Models\PurchaseInvoice  $invoice
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry|null
     *
     * @throws \Exception
     */
    public function issuePurchaseInvoice(PurchaseInvoice $invoice, int $userId): ?JournalEntry
    {
        if (!$invoice->isDraft()) {
            throw new \Exception('Only draft invoices can be issued.');
        }

        // Validate fiscal period
        $this->accountingService->validateFiscalPeriod($invoice->fiscal_period_id);

        // Get accounts
        $accountsPayableAccount = $this->getAccountByCode('AP');
        $purchaseAccount = $this->getAccountByCode('PUR');

        if (!$accountsPayableAccount || !$purchaseAccount) {
            throw new \Exception('Required accounts (AP, PUR) not configured.');
        }

        return DB::transaction(function () use ($invoice, $userId, $accountsPayableAccount, $purchaseAccount) {
            // Update invoice status
            $invoice->update([
                'status' => 'issued',
                'issued_by' => $userId,
                'issued_at' => now(),
            ]);

            // Create journal entry
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($invoice->issue_date);
            $fiscalYear = $fiscalPeriod->fiscalYear;

            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $invoice->issue_date,
                'reference_type' => PurchaseInvoice::class,
                'reference_id' => $invoice->id,
                'description' => "Purchase Invoice: {$invoice->invoice_number}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $invoice->currency;
            $taxAmount = (float) ($invoice->tax_amount ?? 0);
            $grossAmount = (float) $invoice->total;
            // Net amount is always gross - tax
            $netAmount = $grossAmount - $taxAmount;

            $lineNumber = 1;

            // Debit: Purchases (Net amount)
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $purchaseAccount->id,
                'currency_id' => $currency->id,
                'debit' => $netAmount,
                'credit' => 0,
                'amount_base' => $netAmount,
                'description' => "Invoice {$invoice->invoice_number}",
                'line_number' => $lineNumber++,
            ]);

            // Debit: VAT Receivable (if tax exists)
            if ($taxAmount > 0.01) {
                // If tax_breakdown exists, use it; otherwise create a single entry
                if ($invoice->tax_breakdown && !empty($invoice->tax_breakdown)) {
                    $totalTaxFromBreakdown = 0;
                    foreach ($invoice->tax_breakdown as $taxLine) {
                        $taxRateId = $taxLine['tax_rate_id'] ?? null;
                        $lineTaxAmount = (float) ($taxLine['tax_amount'] ?? 0);
                        if ($lineTaxAmount > 0) {
                            if ($taxRateId) {
                                $taxAccount = $this->taxCalculationService->getTaxAccount($taxRateId);
                                if ($taxAccount) {
                                    JournalEntryLine::create([
                                        'tenant_id' => $this->getTenantId(),
                                        'journal_entry_id' => $entry->id,
                                        'account_id' => $taxAccount->id,
                                        'currency_id' => $currency->id,
                                        'debit' => $lineTaxAmount,
                                        'credit' => 0,
                                        'amount_base' => $lineTaxAmount,
                                        'description' => "VAT Receivable ({$taxLine['tax_rate_code']}) for Invoice {$invoice->invoice_number}",
                                        'line_number' => $lineNumber++,
                                    ]);
                                    $totalTaxFromBreakdown += $lineTaxAmount;
                                }
                            }
                        }
                    }
                    // If total tax from breakdown doesn't match, add difference
                    $diff = abs($taxAmount - $totalTaxFromBreakdown);
                    if ($diff > 0.01) {
                        $taxAccount = $this->getAccountByCode('TAX');
                        if (!$taxAccount) {
                            $taxRate = \App\Modules\ERP\Models\TaxRate::where('tenant_id', $this->getTenantId())->first();
                            if ($taxRate) {
                                $taxAccount = $this->taxCalculationService->getTaxAccount($taxRate->id);
                            }
                        }
                        if ($taxAccount) {
                            JournalEntryLine::create([
                                'tenant_id' => $this->getTenantId(),
                                'journal_entry_id' => $entry->id,
                                'account_id' => $taxAccount->id,
                                'currency_id' => $currency->id,
                                'debit' => $diff,
                                'credit' => 0,
                                'amount_base' => $diff,
                                'description' => "VAT Receivable (Adjustment) for Invoice {$invoice->invoice_number}",
                                'line_number' => $lineNumber++,
                            ]);
                        }
                    }
                } else {
                    // If no tax breakdown, create a single VAT Receivable entry
                    $taxAccount = $this->getAccountByCode('TAX');
                    if (!$taxAccount) {
                        $taxRate = \App\Modules\ERP\Models\TaxRate::where('tenant_id', $this->getTenantId())->first();
                        if ($taxRate) {
                            $taxAccount = $this->taxCalculationService->getTaxAccount($taxRate->id);
                        }
                    }

                    if ($taxAccount) {
                        JournalEntryLine::create([
                            'tenant_id' => $this->getTenantId(),
                            'journal_entry_id' => $entry->id,
                            'account_id' => $taxAccount->id,
                            'currency_id' => $currency->id,
                            'debit' => $taxAmount,
                            'credit' => 0,
                            'amount_base' => $taxAmount,
                            'description' => "VAT Receivable for Invoice {$invoice->invoice_number}",
                            'line_number' => $lineNumber++,
                        ]);
                    }
                }
            }

            // Credit: Accounts Payable (Gross amount)
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $accountsPayableAccount->id,
                'currency_id' => $currency->id,
                'debit' => 0,
                'credit' => $grossAmount,
                'amount_base' => $grossAmount,
                'description' => "Accounts Payable for Invoice {$invoice->invoice_number}",
                'line_number' => $lineNumber++,
            ]);

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }

    /**
     * Cancel an invoice.
     *
     * @param  \App\Modules\ERP\Models\SalesInvoice|\App\Modules\ERP\Models\PurchaseInvoice  $invoice
     * @param  int  $userId
     * @return void
     *
     * @throws \Exception
     */
    public function cancelInvoice($invoice, int $userId): void
    {
        if (!$invoice->canBeCancelled()) {
            throw new \Exception('Invoice cannot be cancelled.');
        }

        // Check if there are any payments
        if ($invoice->paymentAllocations()->exists()) {
            throw new \Exception('Cannot cancel invoice with payments. Reverse payments first.');
        }

        DB::transaction(function () use ($invoice, $userId) {
            // Reverse journal entry if issued
            if ($invoice->isIssued()) {
                $journalEntry = JournalEntry::where('tenant_id', $this->getTenantId())
                    ->where('reference_type', get_class($invoice))
                    ->where('reference_id', $invoice->id)
                    ->first();

                if ($journalEntry && $journalEntry->isPosted()) {
                    // Create reversing entry
                    $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($invoice->issue_date);
                    $fiscalYear = $fiscalPeriod->fiscalYear;

                    $reversingEntry = JournalEntry::create([
                        'tenant_id' => $this->getTenantId(),
                        'fiscal_year_id' => $fiscalYear->id,
                        'fiscal_period_id' => $fiscalPeriod->id,
                        'entry_date' => now(),
                        'reference_type' => get_class($invoice),
                        'reference_id' => $invoice->id,
                        'description' => "Reversal of Invoice: {$invoice->invoice_number}",
                        'status' => 'draft',
                        'created_by' => $userId,
                    ]);

                    // Reverse all lines
                    foreach ($journalEntry->lines as $line) {
                        JournalEntryLine::create([
                            'tenant_id' => $this->getTenantId(),
                            'journal_entry_id' => $reversingEntry->id,
                            'account_id' => $line->account_id,
                            'currency_id' => $line->currency_id,
                            'debit' => $line->credit, // Reverse
                            'credit' => $line->debit, // Reverse
                            'amount_base' => $line->amount_base,
                            'description' => "Reversal: {$line->description}",
                            'line_number' => $line->line_number,
                        ]);
                    }

                    // Auto-post the reversing entry
                    $this->accountingService->postJournalEntry($reversingEntry, $userId);
                }
            }

            // Update invoice status
            $invoice->update([
                'status' => 'cancelled',
            ]);
        });
    }

    /**
     * Get account by code.
     *
     * @param  string  $code
     * @return \App\Modules\ERP\Models\Account|null
     */
    protected function getAccountByCode(string $code): ?Account
    {
        return Account::where('tenant_id', $this->getTenantId())
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }
}

