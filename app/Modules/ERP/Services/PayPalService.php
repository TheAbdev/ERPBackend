<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Payment;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Models\PaymentGatewayTransaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment as PayPalPayment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction as PayPalTransaction;
use PayPal\Exception\PayPalConnectionException;

class PayPalService extends BaseService
{
    protected ?ApiContext $apiContext = null;

    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Get PayPal API context.
     *
     * @param  PaymentGateway  $gateway
     * @return ApiContext
     */
    protected function getApiContext(PaymentGateway $gateway): ApiContext
    {
        if (!$this->apiContext) {
            $clientId = $gateway->getCredential('client_id');
            $clientSecret = $gateway->getCredential('client_secret');
            $mode = $gateway->getCredential('mode', 'sandbox'); // sandbox or live

            if (!$clientId || !$clientSecret) {
                throw new \Exception('PayPal credentials not configured.');
            }

            $this->apiContext = new ApiContext(
                new OAuthTokenCredential($clientId, $clientSecret)
            );

            $this->apiContext->setConfig([
                'mode' => $mode,
                'http.ConnectionTimeOut' => 30,
                'log.LogEnabled' => false,
            ]);
        }

        return $this->apiContext;
    }

    /**
     * Create a PayPal payment.
     *
     * @param  PaymentGateway  $gateway
     * @param  Payment  $payment
     * @param  array  $options
     * @return array
     * @throws \Exception
     */
    public function createPayment(PaymentGateway $gateway, Payment $payment, array $options = []): array
    {
        try {
            $apiContext = $this->getApiContext($gateway);

            $payer = new Payer();
            $payer->setPaymentMethod('paypal');

            $amount = new Amount();
            $amount->setCurrency(strtoupper($payment->currency->code ?? 'USD'))
                ->setTotal(number_format($payment->amount, 2, '.', ''));

            $transaction = new PayPalTransaction();
            $transaction->setAmount($amount)
                ->setDescription("Payment: {$payment->payment_number}")
                ->setInvoiceNumber($payment->payment_number);

            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl($options['return_url'] ?? url('/payment/paypal/return'))
                ->setCancelUrl($options['cancel_url'] ?? url('/payment/paypal/cancel'));

            $paypalPayment = new PayPalPayment();
            $paypalPayment->setIntent('sale')
                ->setPayer($payer)
                ->setTransactions([$transaction])
                ->setRedirectUrls($redirectUrls);

            $paypalPayment->create($apiContext);

            // Create gateway transaction record
            $gatewayTransaction = PaymentGatewayTransaction::create([
                'tenant_id' => $this->getTenantId(),
                'payment_gateway_id' => $gateway->id,
                'payment_id' => $payment->id,
                'gateway_transaction_id' => $paypalPayment->getId(),
                'gateway_type' => 'paypal',
                'status' => 'pending',
                'amount' => $payment->amount,
                'currency' => strtoupper($payment->currency->code ?? 'USD'),
                'gateway_response' => $paypalPayment->toArray(),
            ]);

            $approvalUrl = null;
            foreach ($paypalPayment->getLinks() as $link) {
                if ($link->getRel() === 'approval_url') {
                    $approvalUrl = $link->getHref();
                    break;
                }
            }

            return [
                'approval_url' => $approvalUrl,
                'transaction_id' => $gatewayTransaction->id,
                'gateway_transaction_id' => $paypalPayment->getId(),
            ];
        } catch (PayPalConnectionException $e) {
            throw new \Exception("PayPal error: {$e->getData()}");
        } catch (\Exception $e) {
            throw new \Exception("PayPal error: {$e->getMessage()}");
        }
    }

    /**
     * Execute a PayPal payment.
     *
     * @param  PaymentGateway  $gateway
     * @param  string  $paymentId
     * @param  string  $payerId
     * @return array
     * @throws \Exception
     */
    public function executePayment(PaymentGateway $gateway, string $paymentId, string $payerId): array
    {
        try {
            $apiContext = $this->getApiContext($gateway);

            $paypalPayment = PayPalPayment::get($paymentId, $apiContext);

            $execution = new \PayPal\Api\PaymentExecution();
            $execution->setPayerId($payerId);

            $paypalPayment->execute($execution, $apiContext);

            // Find gateway transaction
            $gatewayTransaction = PaymentGatewayTransaction::where('gateway_transaction_id', $paymentId)
                ->where('tenant_id', $this->getTenantId())
                ->firstOrFail();

            $status = $paypalPayment->getState() === 'approved' ? 'completed' : 'failed';

            $gatewayTransaction->update([
                'status' => $status,
                'gateway_response' => $paypalPayment->toArray(),
                'processed_at' => $status === 'completed' ? now() : null,
                'failure_reason' => $status === 'failed' ? 'Payment execution failed' : null,
            ]);

            // Update payment if exists
            if ($gatewayTransaction->payment && $status === 'completed') {
                $gatewayTransaction->payment->update([
                    'payment_method' => 'paypal',
                    'reference_number' => $paymentId,
                ]);
            }

            return [
                'status' => $status,
                'transaction' => $gatewayTransaction,
            ];
        } catch (PayPalConnectionException $e) {
            throw new \Exception("PayPal error: {$e->getData()}");
        } catch (\Exception $e) {
            throw new \Exception("PayPal error: {$e->getMessage()}");
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
        $eventType = $event['event_type'] ?? null;
        $resource = $event['resource'] ?? [];

        switch ($eventType) {
            case 'PAYMENT.SALE.COMPLETED':
                $this->handlePaymentCompleted($gateway, $resource);
                break;
            case 'PAYMENT.SALE.DENIED':
            case 'PAYMENT.SALE.REFUNDED':
                $this->handlePaymentRefunded($gateway, $resource);
                break;
        }
    }

    /**
     * Handle payment completed event.
     *
     * @param  PaymentGateway  $gateway
     * @param  array  $resource
     * @return void
     */
    protected function handlePaymentCompleted(PaymentGateway $gateway, array $resource): void
    {
        $paymentId = $resource['parent_payment'] ?? null;
        if (!$paymentId) {
            return;
        }

        $gatewayTransaction = PaymentGatewayTransaction::where('gateway_transaction_id', $paymentId)
            ->where('tenant_id', $this->getTenantId())
            ->first();

        if ($gatewayTransaction) {
            $gatewayTransaction->update([
                'status' => 'completed',
                'processed_at' => now(),
                'gateway_response' => array_merge($gatewayTransaction->gateway_response ?? [], ['webhook' => $resource]),
            ]);

            // Update payment if exists
            if ($gatewayTransaction->payment) {
                $gatewayTransaction->payment->update([
                    'payment_method' => 'paypal',
                    'reference_number' => $paymentId,
                ]);
            }
        }
    }

    /**
     * Handle payment refunded event.
     *
     * @param  PaymentGateway  $gateway
     * @param  array  $resource
     * @return void
     */
    protected function handlePaymentRefunded(PaymentGateway $gateway, array $resource): void
    {
        $paymentId = $resource['parent_payment'] ?? null;
        if (!$paymentId) {
            return;
        }

        $gatewayTransaction = PaymentGatewayTransaction::where('gateway_transaction_id', $paymentId)
            ->where('tenant_id', $this->getTenantId())
            ->first();

        if ($gatewayTransaction) {
            $gatewayTransaction->update([
                'status' => 'refunded',
                'gateway_response' => array_merge($gatewayTransaction->gateway_response ?? [], ['refund' => $resource]),
            ]);
        }
    }
}



























