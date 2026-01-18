<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Account;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Models\JournalEntryLine;
use App\Modules\ERP\Models\SalesOrder;
use App\Modules\ERP\Models\PurchaseOrder;
use App\Modules\ERP\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Service for automatic accounting postings.
 */
class AutoPostingService extends BaseService
{
    protected AccountingService $accountingService;
    protected StockMovementService $stockMovementService;

    public function __construct(
        TenantContext $tenantContext,
        AccountingService $accountingService,
        StockMovementService $stockMovementService
    ) {
        parent::__construct($tenantContext);
        $this->accountingService = $accountingService;
        $this->stockMovementService = $stockMovementService;
    }

    /**
     * Post accounting entry for sales order delivery.
     *
     * @param  \App\Modules\ERP\Models\SalesOrder  $salesOrder
     * @param  array  $deliveryItems  Array of item deliveries
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry|null
     */
    public function postOnSalesDelivery(SalesOrder $salesOrder, array $deliveryItems, int $userId): ?JournalEntry
    {
        // Get accounts (these should be configured per tenant)
        $accountsReceivableAccount = $this->getAccountByCode('AR'); // Accounts Receivable
        $salesRevenueAccount = $this->getAccountByCode('REV'); // Sales Revenue
        $costOfGoodsSoldAccount = $this->getAccountByCode('COGS'); // Cost of Goods Sold
        $inventoryAccount = $this->getAccountByCode('INV'); // Inventory

        if (! $accountsReceivableAccount || ! $salesRevenueAccount || ! $costOfGoodsSoldAccount || ! $inventoryAccount) {
            // Accounts not configured, skip posting
            return null;
        }

        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($salesOrder->items as $item) {
            $deliveryQty = $deliveryItems[$item->id] ?? 0;
            if ($deliveryQty <= 0) {
                continue;
            }

            $revenue = ($item->unit_price * $deliveryQty) - ($item->discount_amount ?? 0);
            $totalRevenue += $revenue;

            // Calculate cost using FIFO from inventory
            $stockItem = \App\Modules\ERP\Models\StockItem::where('tenant_id', $this->getTenantId())
                ->where('warehouse_id', $salesOrder->warehouse_id)
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();
            $cost = $stockItem ? ($stockItem->average_cost * $deliveryQty) : 0;
            $totalCost += $cost;
        }

        if ($totalRevenue <= 0 && $totalCost <= 0) {
            return null;
        }

        return DB::transaction(function () use ($salesOrder, $totalRevenue, $totalCost, $accountsReceivableAccount, $salesRevenueAccount, $costOfGoodsSoldAccount, $inventoryAccount, $userId) {
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($salesOrder->order_date);
            $fiscalYear = $fiscalPeriod->fiscalYear;

            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $salesOrder->order_date,
                'reference_type' => SalesOrder::class,
                'reference_id' => $salesOrder->id,
                'description' => "Sales delivery: {$salesOrder->order_number}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $salesOrder->currency;

            // Debit: Accounts Receivable
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $accountsReceivableAccount->id,
                'currency_id' => $currency->id,
                'debit' => $totalRevenue,
                'credit' => 0,
                'line_number' => 1,
            ]);

            // Credit: Sales Revenue
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $salesRevenueAccount->id,
                'currency_id' => $currency->id,
                'debit' => 0,
                'credit' => $totalRevenue,
                'line_number' => 2,
            ]);

            if ($totalCost > 0) {
                // Debit: Cost of Goods Sold
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $costOfGoodsSoldAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => $totalCost,
                    'credit' => 0,
                    'line_number' => 3,
                ]);

                // Credit: Inventory
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => 0,
                    'credit' => $totalCost,
                    'line_number' => 4,
                ]);
            }

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }

    /**
     * Post accounting entry for purchase order receipt.
     *
     * @param  \App\Modules\ERP\Models\PurchaseOrder  $purchaseOrder
     * @param  array  $receiveItems  Array of item receipts
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry|null
     */
    public function postOnPurchaseReceipt(PurchaseOrder $purchaseOrder, array $receiveItems, int $userId): ?JournalEntry
    {
        // Get accounts
        $accountsPayableAccount = $this->getAccountByCode('AP'); // Accounts Payable
        $purchaseAccount = $this->getAccountByCode('PUR'); // Purchases
        $inventoryAccount = $this->getAccountByCode('INV'); // Inventory

        if (! $accountsPayableAccount || ! $purchaseAccount || ! $inventoryAccount) {
            return null;
        }

        $totalAmount = 0;
        $totalCost = 0;

        foreach ($purchaseOrder->items as $item) {
            $receiveData = $receiveItems[$item->id] ?? null;
            if (! $receiveData || ($receiveData['quantity'] ?? 0) <= 0) {
                continue;
            }

            $receiveQty = $receiveData['quantity'];
            $unitCost = $receiveData['unit_cost'] ?? $item->unit_cost;
            $amount = $unitCost * $receiveQty;
            $totalAmount += $amount;
            $totalCost += $amount; // For inventory valuation
        }

        if ($totalAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($purchaseOrder, $totalAmount, $totalCost, $accountsPayableAccount, $purchaseAccount, $inventoryAccount, $userId) {
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($purchaseOrder->order_date);
            $fiscalYear = $fiscalPeriod->fiscalYear;

            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $purchaseOrder->order_date,
                'reference_type' => PurchaseOrder::class,
                'reference_id' => $purchaseOrder->id,
                'description' => "Purchase receipt: {$purchaseOrder->order_number}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $currency = $purchaseOrder->currency;

            // Debit: Inventory
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'currency_id' => $currency->id,
                'debit' => $totalCost,
                'credit' => 0,
                'line_number' => 1,
            ]);

            // Credit: Accounts Payable
            JournalEntryLine::create([
                'tenant_id' => $this->getTenantId(),
                'journal_entry_id' => $entry->id,
                'account_id' => $accountsPayableAccount->id,
                'currency_id' => $currency->id,
                'debit' => 0,
                'credit' => $totalAmount,
                'line_number' => 2,
            ]);

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
        });
    }

    /**
     * Post accounting entry for inventory transaction (stock value adjustment).
     *
     * @param  \App\Modules\ERP\Models\InventoryTransaction  $transaction
     * @param  int  $userId
     * @return \App\Modules\ERP\Models\JournalEntry|null
     */
    public function postOnInventoryTransaction(InventoryTransaction $transaction, int $userId): ?JournalEntry
    {
        // Only post for adjustments that affect inventory value
        if (! in_array($transaction->transaction_type, ['adjustment', 'opening_balance'])) {
            return null;
        }

        $inventoryAccount = $this->getAccountByCode('INV');
        $inventoryAdjustmentAccount = $this->getAccountByCode('INV_ADJ');

        if (! $inventoryAccount || ! $inventoryAdjustmentAccount) {
            return null;
        }

        if ($transaction->total_cost <= 0) {
            return null;
        }

        return DB::transaction(function () use ($transaction, $inventoryAccount, $inventoryAdjustmentAccount, $userId) {
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($transaction->transaction_date);
            $fiscalYear = $fiscalPeriod->fiscalYear;

            $entry = JournalEntry::create([
                'tenant_id' => $this->getTenantId(),
                'fiscal_year_id' => $fiscalYear->id,
                'fiscal_period_id' => $fiscalPeriod->id,
                'entry_date' => $transaction->transaction_date,
                'reference_type' => InventoryTransaction::class,
                'reference_id' => $transaction->id,
                'description' => "Inventory {$transaction->transaction_type}: {$transaction->product->sku}",
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            // Get currency from warehouse's company
            $warehouse = $transaction->warehouse;
            $currency = $warehouse->branch->company->defaultCurrency ?? \App\Modules\ERP\Models\Currency::where('tenant_id', $this->getTenantId())->where('is_base', true)->first();

            if (! $currency) {
                // Fallback to any currency for the tenant
                $currency = \App\Modules\ERP\Models\Currency::where('tenant_id', $this->getTenantId())->first();
            }

            if ($transaction->quantity > 0) {
                // Receipt: Debit Inventory, Credit Adjustment
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => $transaction->total_cost,
                    'credit' => 0,
                    'line_number' => 1,
                ]);

                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAdjustmentAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => 0,
                    'credit' => $transaction->total_cost,
                    'line_number' => 2,
                ]);
            } else {
                // Issue: Debit Adjustment, Credit Inventory
                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAdjustmentAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => abs($transaction->total_cost),
                    'credit' => 0,
                    'line_number' => 1,
                ]);

                JournalEntryLine::create([
                    'tenant_id' => $this->getTenantId(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $inventoryAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => 0,
                    'credit' => abs($transaction->total_cost),
                    'line_number' => 2,
                ]);
            }

            // Auto-post the entry
            $this->accountingService->postJournalEntry($entry, $userId);

            return $entry;
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

