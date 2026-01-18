<?php

namespace Tests\Unit\Services;

use App\Modules\ERP\Services\InvoiceService;
use App\Modules\ERP\Models\SalesInvoice;
use App\Modules\ERP\Models\SalesInvoiceItem;
use App\Core\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->service = new InvoiceService();
    }

    /** @test */
    public function it_calculates_invoice_total_correctly(): void
    {
        $invoice = SalesInvoice::factory()->create(['tenant_id' => $this->tenant->id]);

        SalesInvoiceItem::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $this->tenant->id,
            'quantity' => 2,
            'unit_price' => 10.00,
        ]);

        $total = $this->service->calculateTotal($invoice);

        $this->assertEquals(60.00, $total); // 3 items * 2 quantity * 10.00 price
    }

    /** @test */
    public function it_handles_invoice_without_items(): void
    {
        $invoice = SalesInvoice::factory()->create(['tenant_id' => $this->tenant->id]);

        $total = $this->service->calculateTotal($invoice);

        $this->assertEquals(0, $total);
    }
}



