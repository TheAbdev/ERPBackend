<?php

namespace App\Modules\ERP\Services\Reports;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\ProductCategory;
use App\Modules\ERP\Models\Supplier;
use App\Modules\ERP\Models\PurchaseOrder;
use App\Modules\ERP\Models\SalesOrder;
use App\Modules\ERP\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;

class ErpReportsService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    protected function dateFilter(string $column, array $filters, $query)
    {
        if (!empty($filters['start_date'])) {
            $query->whereDate($column, '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate($column, '<=', $filters['end_date']);
        }

        return $query;
    }

    public function products(array $filters = []): array
    {
        $q = Product::where('tenant_id', $this->tenantContext->getTenantId());
        $this->dateFilter('created_at', $filters, $q);

        $total = (clone $q)->count();
        $active = (clone $q)->where('is_active', true)->count();
        $inactive = (clone $q)->where('is_active', false)->count();

        $byType = (clone $q)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($r) => ['label' => $r->type ?? 'Unknown', 'count' => (int)$r->count])
            ->values()
            ->toArray();

        return [
            'cards' => [
                ['label' => 'Total Products', 'value' => $total],
                ['label' => 'Active Products', 'value' => $active],
                ['label' => 'Inactive Products', 'value' => $inactive],
            ],
            'sections' => [
                ['title' => 'Products by Type', 'rows' => $byType],
            ],
        ];
    }

    public function productCategories(array $filters = []): array
    {
        $q = ProductCategory::where('tenant_id', $this->tenantContext->getTenantId());
        $this->dateFilter('created_at', $filters, $q);

        $total = (clone $q)->count();

        return [
            'cards' => [
                ['label' => 'Total Categories', 'value' => $total],
            ],
            'sections' => [],
        ];
    }

    public function suppliers(array $filters = []): array
    {
        $q = Supplier::where('tenant_id', $this->tenantContext->getTenantId());
        $this->dateFilter('created_at', $filters, $q);

        $total = (clone $q)->count();
        $active = (clone $q)->where('is_active', true)->count();
        $inactive = (clone $q)->where('is_active', false)->count();

        return [
            'cards' => [
                ['label' => 'Total Suppliers', 'value' => $total],
                ['label' => 'Active Suppliers', 'value' => $active],
                ['label' => 'Inactive Suppliers', 'value' => $inactive],
            ],
            'sections' => [],
        ];
    }

    public function purchaseOrders(array $filters = []): array
    {
        $q = PurchaseOrder::where('tenant_id', $this->tenantContext->getTenantId());
        $this->dateFilter('order_date', $filters, $q);

        $totalOrders = (clone $q)->count();
        $totalAmount = (clone $q)->sum('total_amount');

        $byStatus = (clone $q)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($r) => ['status' => $r->status ?? 'unknown', 'count' => (int)$r->count])
            ->values()
            ->toArray();

        return [
            'cards' => [
                ['label' => 'Total Purchase Orders', 'value' => $totalOrders],
                ['label' => 'Total Amount', 'value' => (float)$totalAmount],
            ],
            'sections' => [
                ['title' => 'Purchase Orders by Status', 'rows' => $byStatus],
            ],
        ];
    }

    public function salesOrders(array $filters = []): array
    {
        $q = SalesOrder::where('tenant_id', $this->tenantContext->getTenantId());
        $this->dateFilter('order_date', $filters, $q);

        $totalOrders = (clone $q)->count();
        $totalAmount = (clone $q)->sum('total_amount');

        $byStatus = (clone $q)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($r) => ['status' => $r->status ?? 'unknown', 'count' => (int)$r->count])
            ->values()
            ->toArray();

        return [
            'cards' => [
                ['label' => 'Total Sales Orders', 'value' => $totalOrders],
                ['label' => 'Total Amount', 'value' => (float)$totalAmount],
            ],
            'sections' => [
                ['title' => 'Sales Orders by Status', 'rows' => $byStatus],
            ],
        ];
    }

    public function invoices(array $filters = []): array
    {
        $q = SalesInvoice::where('tenant_id', $this->tenantContext->getTenantId());
        $this->dateFilter('issue_date', $filters, $q);

        $totalInvoices = (clone $q)->count();
        $totalValue = (clone $q)->sum('total');
        $paidCount = (clone $q)->where('status', 'paid')->count();
        $winRate = $totalInvoices > 0 ? round(($paidCount / $totalInvoices) * 100, 2) : 0;

        $byStatus = (clone $q)
            ->select('status', DB::raw('count(*) as count'), DB::raw('SUM(total) as value'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status ?? 'unknown',
                'count' => (int)$r->count,
                'value' => (float)($r->value ?? 0),
            ])
            ->values()
            ->toArray();

        return [
            'cards' => [
                ['label' => 'Total Invoices', 'value' => $totalInvoices],
                ['label' => 'Total Value', 'value' => (float)$totalValue],
                ['label' => 'Paid Rate', 'value' => $winRate . '%'],
            ],
            'sections' => [
                ['title' => 'Invoices by Status', 'rows' => $byStatus],
            ],
        ];
    }
}
