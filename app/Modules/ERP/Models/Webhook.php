<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends ErpBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'erp_webhooks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'url',
        'secret',
        'is_active',
        'module',
        'event_types',
        'last_delivery_status',
        'last_delivery_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_types' => 'array',
            'is_active' => 'boolean',
            'last_delivery_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant that owns the webhook.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the webhook deliveries.
     *
     * @return HasMany
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if webhook is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if webhook subscribes to event type.
     *
     * @param  string  $eventType
     * @return bool
     */
    public function subscribesTo(string $eventType): bool
    {
        return in_array($eventType, $this->event_types ?? []);
    }

    /**
     * Generate webhook signature.
     *
     * @param  array  $payload
     * @return string
     */
    public function generateSignature(array $payload): string
    {
        if (!$this->secret) {
            return '';
        }

        $payloadString = json_encode($payload);
        return hash_hmac('sha256', $payloadString, $this->secret);
    }
}

