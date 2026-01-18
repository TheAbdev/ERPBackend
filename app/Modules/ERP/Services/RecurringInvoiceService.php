<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\RecurringInvoice;
use App\Modules\ERP\Models\SalesInvoice;
use App\Modules\ERP\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceService
{
    protected TenantContext $tenantContext;
    protected InvoiceService $invoiceService;

    public function __construct(TenantContext $tenantContext, InvoiceService $invoiceService)
    {
        $this->tenantContext = $tenantContext;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Generate invoices for recurring invoices due today.
     */
    public function generateDueInvoices(): int
    {
        $count = 0;
        $recurringInvoices = RecurringInvoice::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('is_active', true)
            ->where('next_run_date', '<=', now()->toDateString())
            ->get();

        foreach ($recurringInvoices as $recurringInvoice) {
            if ($this->shouldGenerate($recurringInvoice)) {
                $this->generateInvoice($recurringInvoice);
                $this->updateNextRunDate($recurringInvoice);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if invoice should be generated.
     */
    protected function shouldGenerate(RecurringInvoice $recurringInvoice): bool
    {
        // Check end date
        if ($recurringInvoice->end_date && now()->toDateString() > $recurringInvoice->end_date) {
            $recurringInvoice->update(['is_active' => false]);
            return false;
        }

        // Check occurrences limit
        if ($recurringInvoice->occurrences && $recurringInvoice->generated_count >= $recurringInvoice->occurrences) {
            $recurringInvoice->update(['is_active' => false]);
            return false;
        }

        return true;
    }

    /**
     * Generate invoice from recurring template.
     */
    protected function generateInvoice(RecurringInvoice $recurringInvoice): SalesInvoice
    {
        $invoiceData = $recurringInvoice->invoice_data;
        $fiscalPeriod = $this->getCurrentFiscalPeriod();

        $invoice = SalesInvoice::create([
            'tenant_id' => $recurringInvoice->tenant_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'fiscal_year_id' => $fiscalPeriod->fiscal_year_id,
            'fiscal_period_id' => $fiscalPeriod->id,
            'currency_id' => $recurringInvoice->currency_id,
            'customer_name' => $recurringInvoice->customer_name,
            'customer_email' => $recurringInvoice->customer_email,
            'customer_address' => $recurringInvoice->customer_address,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => $this->calculateDueDate($recurringInvoice),
            'subtotal' => $invoiceData['subtotal'] ?? 0,
            'tax_amount' => $invoiceData['tax_amount'] ?? 0,
            'total' => $invoiceData['total'] ?? 0,
            'balance_due' => $invoiceData['total'] ?? 0,
            'notes' => $recurringInvoice->notes,
            'created_by' => $recurringInvoice->created_by,
        ]);

        // Create invoice items
        if (isset($invoiceData['items'])) {
            foreach ($invoiceData['items'] as $item) {
                $invoice->items()->create([
                    'tenant_id' => $recurringInvoice->tenant_id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'net_amount' => $item['quantity'] * $item['unit_price'],
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'total' => ($item['quantity'] * $item['unit_price']) + ($item['tax_amount'] ?? 0),
                ]);
            }
        }

        $recurringInvoice->increment('generated_count');
        $recurringInvoice->update(['last_run_date' => now()->toDateString()]);

        return $invoice;
    }

    /**
     * Update next run date.
     */
    protected function updateNextRunDate(RecurringInvoice $recurringInvoice): void
    {
        $nextDate = match ($recurringInvoice->frequency) {
            'daily' => now()->addDays($recurringInvoice->interval),
            'weekly' => now()->addWeeks($recurringInvoice->interval),
            'monthly' => now()->addMonths($recurringInvoice->interval)->day($recurringInvoice->day_of_month ?? now()->day),
            'quarterly' => now()->addMonths($recurringInvoice->interval * 3),
            'yearly' => now()->addYears($recurringInvoice->interval),
            default => now()->addMonth(),
        };

        $recurringInvoice->update(['next_run_date' => $nextDate->toDateString()]);
    }

    /**
     * Calculate due date based on frequency.
     */
    protected function calculateDueDate(RecurringInvoice $recurringInvoice): string
    {
        return match ($recurringInvoice->frequency) {
            'daily' => now()->addDays(7)->toDateString(),
            'weekly' => now()->addWeeks(2)->toDateString(),
            'monthly' => now()->addMonth()->toDateString(),
            'quarterly' => now()->addMonths(3)->toDateString(),
            'yearly' => now()->addYear()->toDateString(),
            default => now()->addMonth()->toDateString(),
        };
    }

    /**
     * Generate invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = now()->year;
        $lastInvoice = SalesInvoice::where('tenant_id', $this->tenantContext->getTenantId())
            ->where('invoice_number', 'like', "{$prefix}{$year}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get current fiscal period.
     */
    protected function getCurrentFiscalPeriod()
    {
        return DB::table('fiscal_periods')
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->first();
    }
}

