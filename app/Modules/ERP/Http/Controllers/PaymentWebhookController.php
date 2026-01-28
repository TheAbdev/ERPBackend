<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\PaymentGateway;
use App\Modules\ERP\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Handle Stripe webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stripe(Request $request): JsonResponse
    {
        try {
            $gateway = PaymentGateway::where('tenant_id', $request->user()->tenant_id ?? 1)
                ->where('type', 'stripe')
                ->where('is_active', true)
                ->first();

            if (!$gateway) {
                return response()->json(['error' => 'Gateway not found'], 404);
            }

            $payload = $request->all();
            $this->paymentGatewayService->handleWebhook($gateway, $payload);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle PayPal webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paypal(Request $request): JsonResponse
    {
        try {
            $gateway = PaymentGateway::where('tenant_id', $request->user()->tenant_id ?? 1)
                ->where('type', 'paypal')
                ->where('is_active', true)
                ->first();

            if (!$gateway) {
                return response()->json(['error' => 'Gateway not found'], 404);
            }

            $payload = $request->all();
            $this->paymentGatewayService->handleWebhook($gateway, $payload);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('PayPal webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}



























