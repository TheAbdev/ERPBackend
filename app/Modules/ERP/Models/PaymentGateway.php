<?php

namespace App\Modules\ERP\Models;

use App\Core\Traits\BelongsToTenant;
use App\Modules\ERP\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'is_active',
        'is_default',
        'credentials',
        'settings',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            // credentials is handled by mutator/accessor, don't cast it
            'settings' => 'array',
        ];
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($gateway) {
            // If this is set as default, unset other defaults for this tenant
            if ($gateway->is_default) {
                static::where('tenant_id', $gateway->tenant_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($gateway) {
            // If this is set as default, unset other defaults for this tenant
            if ($gateway->is_default && !$gateway->getOriginal('is_default')) {
                static::where('tenant_id', $gateway->tenant_id)
                    ->where('id', '!=', $gateway->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the tenant that owns the payment gateway.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the payment gateway transactions.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentGatewayTransaction::class);
    }

    /**
     * Get encrypted credentials.
     *
     * @param  array|null  $value
     * @return array|null
     */
    public function getCredentialsAttribute($value): ?array
    {
        if (!$value) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Exception $e) {
            return json_decode($value, true);
        }
    }

    /**
     * Set encrypted credentials.
     *
     * @param  array|null  $value
     * @return void
     */
    public function setCredentialsAttribute(?array $value): void
    {
        if ($value) {
            $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['credentials'] = null;
        }
    }

    /**
     * Get a specific credential value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getCredential(string $key, $default = null)
    {
        $credentials = $this->credentials;
        return $credentials[$key] ?? $default;
    }

    /**
     * Check if gateway is Stripe.
     *
     * @return bool
     */
    public function isStripe(): bool
    {
        return $this->type === 'stripe';
    }

    /**
     * Check if gateway is PayPal.
     *
     * @return bool
     */
    public function isPayPal(): bool
    {
        return $this->type === 'paypal';
    }

    /**
     * Check if gateway is bank transfer.
     *
     * @return bool
     */
    public function isBankTransfer(): bool
    {
        return $this->type === 'bank_transfer';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return \Database\Factories\PaymentGatewayFactory::new();
    }
}

