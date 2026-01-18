<?php

namespace App\Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends ErpBaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'erp_system_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'module',
        'type',
        'description',
        'is_encrypted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns the setting.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Core\Models\Tenant::class);
    }

    /**
     * Get the decrypted value.
     *
     * @return mixed
     */
    public function getDecryptedValue()
    {
        if (!$this->is_encrypted) {
            return $this->getTypedValue();
        }

        try {
            $decrypted = Crypt::decryptString($this->value);
            return $this->castValue($decrypted);
        } catch (\Exception $e) {
            return $this->getTypedValue();
        }
    }

    /**
     * Set encrypted value.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setEncryptedValue($value): void
    {
        $this->value = Crypt::encryptString((string) $value);
        $this->is_encrypted = true;
    }

    /**
     * Get typed value.
     *
     * @return mixed
     */
    protected function getTypedValue()
    {
        return $this->castValue($this->value);
    }

    /**
     * Cast value based on type.
     *
     * @param  string  $value
     * @return mixed
     */
    protected function castValue(string $value)
    {
        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}

