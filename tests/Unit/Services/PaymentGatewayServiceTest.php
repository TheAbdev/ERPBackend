<?php

namespace Tests\Unit\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentGatewayService $paymentGatewayService;
    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
        $this->paymentGatewayService = app(PaymentGatewayService::class);
    }

    public function test_can_get_active_gateway(): void
    {
        $this->markTestSkipped('Requires tenant setup');
        // TODO: Implement test
    }

    public function test_can_process_payment(): void
    {
        $this->markTestSkipped('Requires payment gateway setup');
        // TODO: Implement test
    }
}





