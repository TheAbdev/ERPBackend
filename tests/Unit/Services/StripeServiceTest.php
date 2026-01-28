<?php

namespace Tests\Unit\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StripeService $stripeService;
    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContext::class);
        $this->stripeService = app(StripeService::class);
    }

    public function test_can_create_payment_intent(): void
    {
        $this->markTestSkipped('Requires Stripe API keys');
        // TODO: Implement test with mock Stripe client
    }

    public function test_can_confirm_payment_intent(): void
    {
        $this->markTestSkipped('Requires Stripe API keys');
        // TODO: Implement test
    }

    public function test_can_handle_webhook(): void
    {
        $this->markTestSkipped('Requires Stripe webhook setup');
        // TODO: Implement test
    }
}



























