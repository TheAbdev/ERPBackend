<?php

namespace App\Modules\ERP\Services;

use App\Core\Services\TenantContext;
use App\Modules\ERP\Models\Webhook;
use App\Modules\ERP\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing webhooks.
 */
class WebhookService extends BaseService
{
    /**
     * Trigger an event and deliver to subscribed webhooks.
     *
     * @param  string  $module
     * @param  string  $eventType
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return void
     */
    public function triggerEvent(string $module, string $eventType, Model $entity): void
    {
        // Get active webhooks for this module and event type
        $webhooks = Webhook::where('tenant_id', $this->getTenantId())
            ->where('module', $module)
            ->where('is_active', true)
            ->get()
            ->filter(function ($webhook) use ($eventType) {
                return $webhook->subscribesTo($eventType);
            });

        foreach ($webhooks as $webhook) {
            $this->queueDelivery($webhook, $eventType, $entity);
        }
    }

    /**
     * Trigger an event with explicit tenant ID and deliver to subscribed webhooks.
     *
     * @param  string  $module
     * @param  string  $eventType
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @param  int  $tenantId
     * @return void
     */
    public function triggerEventWithTenant(string $module, string $eventType, Model $entity, int $tenantId): void
    {
        // Get active webhooks for this module and event type
        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->where('module', $module)
            ->where('is_active', true)
            ->get()
            ->filter(function ($webhook) use ($eventType) {
                return $webhook->subscribesTo($eventType);
            });

        foreach ($webhooks as $webhook) {
            $this->queueDelivery($webhook, $eventType, $entity);
        }
    }

    /**
     * Queue webhook delivery.
     *
     * @param  \App\Modules\ERP\Models\Webhook  $webhook
     * @param  string  $eventType
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return \App\Modules\ERP\Models\WebhookDelivery
     */
    public function queueDelivery(Webhook $webhook, string $eventType, Model $entity): WebhookDelivery
    {
        $payload = $this->buildPayload($eventType, $entity);

        $delivery = WebhookDelivery::create([
            'tenant_id' => $this->getTenantId(),
            'webhook_id' => $webhook->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'pending',
            'attempts' => 0,
        ]);

        // Dispatch job to deliver webhook asynchronously
        \App\Jobs\DeliverWebhookJob::dispatch($delivery);

        return $delivery;
    }

    /**
     * Deliver webhook.
     *
     * @param  \App\Modules\ERP\Models\WebhookDelivery  $delivery
     * @return bool
     */
    public function deliver(WebhookDelivery $delivery): bool
    {
        // Refresh delivery to get latest data
        $delivery->refresh();
        $webhook = $delivery->webhook;

        // Check if webhook is still active
        if (!$webhook || !$webhook->is_active) {
            $delivery->markFailure('Webhook is inactive or deleted');
            return false;
        }

        try {
            $payload = $delivery->payload;
            $signature = $webhook->generateSignature($payload);

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $delivery->event_type,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();

            if ($response->successful()) {
                $delivery->markSuccess($statusCode, $responseBody);
                $webhook->refresh();
                $webhook->update([
                    'last_delivery_status' => 'success',
                    'last_delivery_at' => now(),
                ]);
                return true;
            } else {
                $delivery->markFailure(
                    "HTTP {$statusCode}: {$responseBody}",
                    $statusCode,
                    $responseBody
                );
                $webhook->refresh();
                $webhook->update([
                    'last_delivery_status' => 'failure',
                    'last_delivery_at' => now(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $delivery->markFailure($e->getMessage());
            $webhook->refresh();
            $webhook->update([
                'last_delivery_status' => 'failure',
                'last_delivery_at' => now(),
            ]);

            Log::error('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retry failed webhook deliveries.
     *
     * @param  int  $maxAttempts
     * @return int
     */
    public function retryFailedDeliveries(int $maxAttempts = 3): int
    {
        $failedDeliveries = WebhookDelivery::where('tenant_id', $this->getTenantId())
            ->where('status', 'failure')
            ->where('attempts', '<', $maxAttempts)
            ->get();

        $retried = 0;

        foreach ($failedDeliveries as $delivery) {
            \App\Jobs\DeliverWebhookJob::dispatch($delivery);
            $retried++;
        }

        return $retried;
    }

    /**
     * Build webhook payload.
     *
     * @param  string  $eventType
     * @param  \Illuminate\Database\Eloquent\Model  $entity
     * @return array
     */
    protected function buildPayload(string $eventType, Model $entity): array
    {
        return [
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $entity->id,
                'type' => class_basename($entity),
                'attributes' => $entity->toArray(),
            ],
            'tenant_id' => $this->getTenantId(),
        ];
    }
}

