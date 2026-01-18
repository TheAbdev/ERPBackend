<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends ErpBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'erp_webhook_deliveries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'webhook_id',
        'event_type',
        'payload',
        'status',
        'response_code',
        'response_body',
        'error_message',
        'attempts',
        'delivered_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the delivery.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the webhook.
     *
     * @return BelongsTo
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Mark delivery as success.
     *
     * @param  int  $responseCode
     * @param  string|null  $responseBody
     * @return bool
     */
    public function markSuccess(int $responseCode, ?string $responseBody = null): bool
    {
        return $this->update([
            'status' => 'success',
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark delivery as failure.
     *
     * @param  string  $errorMessage
     * @param  int|null  $responseCode
     * @param  string|null  $responseBody
     * @return bool
     */
    public function markFailure(string $errorMessage, ?int $responseCode = null, ?string $responseBody = null): bool
    {
        return $this->update([
            'status' => 'failure',
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * Check if delivery is successful.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if delivery failed.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return $this->status === 'failure';
    }

    /**
     * Check if delivery is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

