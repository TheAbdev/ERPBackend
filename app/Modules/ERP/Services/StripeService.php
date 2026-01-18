<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Models\PaymentGatewayTransaction;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService extends BaseService
{
    protected ?StripeClient $stripe = null;

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Get Stripe client instance.
     *
     * @param  PaymentGateway  $gateway
     * @return StripeClient
     */
    protected function getStripeClient(PaymentGateway $gateway): StripeClient
    {
        if (!$this->stripe) {
            $secretKey = $gateway->getCredential('secret_key');
            if (!$secretKey) {
                throw new \Exception('Stripe secret key not configured.');
            }
            $this->stripe = new StripeClient($secretKey);
        }

        return $this->stripe;
    }

    /**
     * Create a payment intent.
     *
     * @param  PaymentGateway  $gateway
     * @param  Payment  $payment
     * @param  array  $options
     * @return array
     * @throws \Exception
     */
    public function createPaymentIntent(PaymentGateway $gateway, Payment $payment, array $options = []): array
    {
        try {
            $stripe = $this->getStripeClient($gateway);

            $intent = $stripe->paymentIntents->create([
                'amount' => (int) ($payment->amount * 100), // Convert to cents
                'currency' => strtolower($payment->currency->code ?? 'usd'),
                'description' => "Payment: {$payment->payment_number}",
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'tenant_id' => $payment->tenant_id,
                ],
                ...$options,
            ]);

            // Create gateway transaction record
            $gatewayTransaction = PaymentGatewayTransaction::create([
                'tenant_id' => $this->getTenantId(),
                'payment_gateway_id' => $gateway->id,
                'payment_id' => $payment->id,
                'gateway_transaction_id' => $intent->id,
                'gateway_type' => 'stripe',
                'status' => 'pending',
                'amount' => $payment->amount,
                'currency' => strtolower($payment->currency->code ?? 'usd'),
                'gateway_response' => $intent->toArray(),
            ]);

            return [
                'client_secret' => $intent->client_secret,
                'transaction_id' => $gatewayTransaction->id,
                'gateway_transaction_id' => $intent->id,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception("Stripe error: {$e->getMessage()}");
        }
    }

    /**
     * Confirm a payment intent.
     *
     * @param  PaymentGateway  $gateway
     * @param  string  $paymentIntentId
     * @return array
     * @throws \Exception
     */
    public function confirmPaymentIntent(PaymentGateway $gateway, string $paymentIntentId): array
    {
        try {
            $stripe = $this->getStripeClient($gateway);
            $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

            // Find gateway transaction
            $gatewayTransaction = PaymentGatewayTransaction::where('gateway_transaction_id', $paymentIntentId)
                ->where('tenant_id', $this->getTenantId())
                ->firstOrFail();

            // Update status based on intent status
            $status = match ($intent->status) {
                'succeeded' => 'completed',
                'processing' => 'processing',
                'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
                'canceled' => 'cancelled',
                default => 'failed',
            };

            $gatewayTransaction->update([
                'status' => $status,
                'gateway_response' => $intent->toArray(),
                'processed_at' => $status === 'completed' ? now() : null,
                'failure_reason' => $status === 'failed' ? ($intent->last_payment_error->message ?? 'Payment failed') : null,
            ]);

            return [
                'status' => $status,
                'transaction' => $gatewayTransaction,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception("Stripe error: {$e->getMessage()}");
        }
    }

    /**
     * Handle webhook event.
     *
     * @param  PaymentGateway  $gateway
     * @param  array  $event
     * @return void
     */
    public function handleWebhook(PaymentGateway $gateway, array $event): void
    {
        $eventType = $event['type'] ?? null;
        $data = $event['data']['object'] ?? [];

        switch ($eventType) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($gateway, $data);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($gateway, $data);
                break;
            case 'charge.refunded':
                $this->handleRefund($gateway, $data);
                break;
        }
    }

    /**
     * Handle payment succeeded event.
     *
     * @param  PaymentGateway  $gateway
     * @param  array  $data
     * @return void
     */
    protected function handlePaymentSucceeded(PaymentGateway $gateway, array $data): void
    {
        $gatewayTransaction = PaymentGatewayTransaction::where('gateway_transaction_id', $data['id'])
            ->where('tenant_id', $this->getTenantId())
            ->first();

        if ($gatewayTransaction) {
            $gatewayTransaction->update([
                'status' => 'completed',
                'processed_at' => now(),
                'gateway_response' => $data,
            ]);

            // Update payment if exists
            if ($gatewayTransaction->payment) {
                $gatewayTransaction->payment->update([
                    'payment_method' => 'stripe',
                    'reference_number' => $data['id'],
                ]);
            }
        }
    }

    /**
     * Handle payment failed event.
     *
     * @param  PaymentGateway  $gateway
     * @param  array  $data
     * @return void
     */
    protected function handlePaymentFailed(PaymentGateway $gateway, array $data): void
    {
        $gatewayTransaction = PaymentGatewayTransaction::where('gateway_transaction_id', $data['id'])
            ->where('tenant_id', $this->getTenantId())
            ->first();

        if ($gatewayTransaction) {
            $gatewayTransaction->update([
                'status' => 'failed',
                'failure_reason' => $data['last_payment_error']['message'] ?? 'Payment failed',
                'gateway_response' => $data,
            ]);
        }
    }

    /**
     * Handle refund event.
     *
     * @param  PaymentGateway  $gateway
     * @param  array  $data
     * @return void
     */
    protected function handleRefund(PaymentGateway $gateway, array $data): void
    {
        $gatewayTransaction = PaymentGatewayTransaction::where('gateway_transaction_id', $data['payment_intent'])
            ->where('tenant_id', $this->getTenantId())
            ->first();

        if ($gatewayTransaction) {
            $gatewayTransaction->update([
                'status' => 'refunded',
                'gateway_response' => array_merge($gatewayTransaction->gateway_response ?? [], ['refund' => $data]),
            ]);
        }
    }

    /**
     * Create a refund.
     *
     * @param  PaymentGateway  $gateway
     * @param  PaymentGatewayTransaction  $transaction
     * @param  float|null  $amount
     * @return array
     * @throws \Exception
     */
    public function createRefund(PaymentGateway $gateway, PaymentGatewayTransaction $transaction, ?float $amount = null): array
    {
        try {
            $stripe = $this->getStripeClient($gateway);

            $refund = $stripe->refunds->create([
                'payment_intent' => $transaction->gateway_transaction_id,
                'amount' => $amount ? (int) ($amount * 100) : null,
            ]);

            $transaction->update([
                'status' => 'refunded',
                'gateway_response' => array_merge($transaction->gateway_response ?? [], ['refund' => $refund->toArray()]),
            ]);

            return $refund->toArray();
        } catch (ApiErrorException $e) {
            throw new \Exception("Stripe refund error: {$e->getMessage()}");
        }
    }
}

