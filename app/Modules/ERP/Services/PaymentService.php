<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Models\PaymentAllocation;
use App\Modules\ERP\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling payment operations.
 */
class PaymentService extends BaseService
{
    protected AccountingService $accountingService;
    protected WorkflowService $workflowService;

    public function __construct(
        TenantContext $tenantContext,
        AccountingService $accountingService,
        WorkflowService $workflowService
    ) {
        parent::__construct($tenantContext);
        $this->accountingService = $accountingService;
        $this->workflowService = $workflowService;
    }

    /**
     * Apply payment to invoices.
     *
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @param  array  $allocations  Array of ['invoice_type' => string, 'invoice_id' => int, 'amount' => float]
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry|null
     *
     * @throws \Exception
     */
    public function applyPayment(Payment $payment, array $allocations, int $userId): ?JournalEntry
    {
        // Check if workflow approval is required
        if ($this->workflowService->requiresApproval($payment)) {
            // Start workflow instead of applying directly
            $this->workflowService->startWorkflow($payment, $userId);

            // Update payment status to pending_approval
            $payment->update(['status' => 'pending_approval']);

            throw new \Exception('Payment requires approval. Workflow has been initiated.');
        }

        // Validate fiscal period
        $this->accountingService->validateFiscalPeriod($payment->fiscal_period_id);

        // Validate total allocations don't exceed payment amount
        $totalAllocated = array_sum(array_column($allocations, 'amount'));
        if ($totalAllocated > $payment->amount) {
            throw new \Exception('Total allocations cannot exceed payment amount.');
        }

        // Get accounts based on payment type
        if ($payment->isIncoming()) {
            $cashAccount = $this->getAccountByCode('CASH') ?? $this->getAccountByCode('BANK');
            $arAccount = $this->getAccountByCode('AR');
        } else {
            $cashAccount = $this->getAccountByCode('CASH') ?? $this->getAccountByCode('BANK');
            $apAccount = $this->getAccountByCode('AP');
        }

        if (!$cashAccount) {
            throw new \Exception('Cash or Bank account not configured.');
        }

        if ($payment->isIncoming() && !$arAccount) {
            throw new \Exception('Accounts Receivable account not configured.');
        }

        if ($payment->isOutgoing() && !$apAccount) {
            throw new \Exception('Accounts Payable account not configured.');
        }

        return DB::transaction(function () use ($payment, $allocations, $userId, $cashAccount, $arAccount, $apAccount) {
            // Create allocations
            foreach ($allocations as $allocationData) {
                PaymentAllocation::create([
                    'tenant_id' => $this->getTenantId(),
                    'payment_id' => $payment->id,
                    'invoice_type' => $allocationData['invoice_type'],
                    'invoice_id' => $allocationData['invoice_id'],
                    'amount' => $allocationData['amount'],
                ]);
            }

            // Create journal entry
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($payment->payment_date);
            $fiscalYear = $fiscalPeriod->fiscalYear;

            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $payment->payment_date,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'description' => "Payment: {$payment->payment_number}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $payment->currency;
            $lineNumber = 1;

            if ($payment->isIncoming()) {
                // Debit: Cash/Bank
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $cashAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'amount_base' => $payment->amount,
                    'description' => "Payment received: {$payment->payment_number}",
                    'line_number' => $lineNumber++,
                ]);

                // Credit: Accounts Receivable
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $arAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'amount_base' => $payment->amount,
                    'description' => "AR payment: {$payment->payment_number}",
                    'line_number' => $lineNumber++,
                ]);
            } else {
                // Debit: Accounts Payable
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $apAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'amount_base' => $payment->amount,
                    'description' => "AP payment: {$payment->payment_number}",
                    'line_number' => $lineNumber++,
                ]);

                // Credit: Cash/Bank
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $cashAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'amount_base' => $payment->amount,
                    'description' => "Payment made: {$payment->payment_number}",
                    'line_number' => $lineNumber++,
                ]);
            }

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }

    /**
     * Reverse a payment.
     *
     * @param  \App\Modules\ERP\Models\Payment  $payment
     * @param  int  $userId
     * @return void
     *
     * @throws \Exception
     */
    public function reversePayment(Payment $payment, int $userId): void
    {
        if ($payment->allocations()->count() === 0) {
            throw new \Exception('Payment has no allocations to reverse.');
        }

        DB::transaction(function () use ($payment, $userId) {
            // Find and reverse journal entry
            $journalEntry = JournalEntry::where('tenant_id', $this->getTenantId())
                ->where('reference_type', Payment::class)
                ->where('reference_id', $payment->id)
                ->first();

            if ($journalEntry && $journalEntry->isPosted()) {
                // Create reversing entry
                $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($payment->payment_date);
                $fiscalYear = $fiscalPeriod->fiscalYear;

                $reversingEntry = JournalEntry::create([
                    'tenant_id' => $this->getTenantId(),
                    'fiscal_year_id' => $fiscalYear->id,
                    'fiscal_period_id' => $fiscalPeriod->id,
                    'entry_date' => now(),
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'description' => "Reversal of Payment: {$payment->payment_number}",
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

            // Delete allocations (this will trigger invoice balance updates)
            $payment->allocations()->delete();
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

