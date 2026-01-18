<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Models\PaymentGatewayTransaction;
use Illuminate\Support\Facades\DB;

class PaymentGatewayService extends BaseService
{
    protected StripeService $stripeService;
    protected PayPalService $payPalService;

    public function __construct(
        TenantContext $tenantContext,
        StripeService $stripeService,
        PayPalService $payPalService
    ) {
        parent::__construct($tenantContext);
        $this->stripeService = $stripeService;
        $this->payPalService = $payPalService;
    }

    /**
     * Get active payment gateway for tenant.
     *
     * @param  string|null  $type
     * @return PaymentGateway|null
     */
    public function getActiveGateway(?string $type = null): ?PaymentGateway
    {
        $query = PaymentGateway::where('tenant_id', $this->getTenantId())
            ->where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        } else {
            $query->where('is_default', true);
        }

        return $query->first();
    }

    /**
     * Process payment through gateway.
     *
     * @param  Payment  $payment
     * @param  PaymentGateway|null  $gateway
     * @param  array  $options
     * @return array
     * @throws \Exception
     */
    public function processPayment(Payment $payment, ?PaymentGateway $gateway = null, array $options = []): array
    {
        if (!$gateway) {
            $gateway = $this->getActiveGateway();
            if (!$gateway) {
                throw new \Exception('No active payment gateway configured.');
            }
        }

        if (!$gateway->is_active) {
            throw new \Exception('Payment gateway is not active.');
        }

        return match ($gateway->type) {
            'stripe' => $this->stripeService->createPaymentIntent($gateway, $payment, $options),
            'paypal' => $this->payPalService->createPayment($gateway, $payment, $options),
            'bank_transfer' => $this->processBankTransfer($payment, $gateway),
            default => throw new \Exception("Unsupported payment gateway type: {$gateway->type}"),
        };
    }

    /**
     * Process bank transfer payment.
     *
     * @param  Payment  $payment
     * @param  PaymentGateway  $gateway
     * @return array
     */
    protected function processBankTransfer(Payment $payment, PaymentGateway $gateway): array
    {
        $gatewayTransaction = PaymentGatewayTransaction::create([
            'tenant_id' => $this->getTenantId(),
            'payment_gateway_id' => $gateway->id,
            'payment_id' => $payment->id,
            'gateway_transaction_id' => 'BT-' . $payment->payment_number,
            'gateway_type' => 'bank_transfer',
            'status' => 'pending',
            'amount' => $payment->amount,
            'currency' => $payment->currency->code ?? 'USD',
            'payment_method' => 'bank_transfer',
        ]);

        return [
            'transaction_id' => $gatewayTransaction->id,
            'gateway_transaction_id' => $gatewayTransaction->gateway_transaction_id,
            'status' => 'pending',
        ];
    }

    /**
     * Confirm payment.
     *
     * @param  PaymentGateway  $gateway
     * @param  string  $transactionId
     * @param  array  $data
     * @return array
     * @throws \Exception
     */
    public function confirmPayment(PaymentGateway $gateway, string $transactionId, array $data = []): array
    {
        return match ($gateway->type) {
            'stripe' => $this->stripeService->confirmPaymentIntent($gateway, $transactionId),
            'paypal' => $this->payPalService->executePayment($gateway, $transactionId, $data['payer_id'] ?? ''),
            default => throw new \Exception("Unsupported payment gateway type: {$gateway->type}"),
        };
    }

    /**
     * Handle webhook from payment gateway.
     *
     * @param  PaymentGateway  $gateway
     * @param  array  $payload
     * @return void
     * @throws \Exception
     */
    public function handleWebhook(PaymentGateway $gateway, array $payload): void
    {
        match ($gateway->type) {
            'stripe' => $this->stripeService->handleWebhook($gateway, $payload),
            'paypal' => $this->payPalService->handleWebhook($gateway, $payload),
            default => throw new \Exception("Unsupported payment gateway type: {$gateway->type}"),
        };
    }

    /**
     * Create refund.
     *
     * @param  PaymentGatewayTransaction  $transaction
     * @param  float|null  $amount
     * @return array
     * @throws \Exception
     */
    public function createRefund(PaymentGatewayTransaction $transaction, ?float $amount = null): array
    {
        $gateway = $transaction->paymentGateway;

        return match ($gateway->type) {
            'stripe' => $this->stripeService->createRefund($gateway, $transaction, $amount),
            'paypal' => throw new \Exception('PayPal refunds must be processed through PayPal dashboard.'),
            default => throw new \Exception("Unsupported payment gateway type: {$gateway->type}"),
        };
    }

    /**
     * Get transaction by gateway transaction ID.
     *
     * @param  string  $gatewayTransactionId
     * @param  string  $gatewayType
     * @return PaymentGatewayTransaction|null
     */
    public function getTransactionByGatewayId(string $gatewayTransactionId, string $gatewayType): ?PaymentGatewayTransaction
    {
        return PaymentGatewayTransaction::where('tenant_id', $this->getTenantId())
            ->where('gateway_transaction_id', $gatewayTransactionId)
            ->where('gateway_type', $gatewayType)
            ->first();
    }
}





