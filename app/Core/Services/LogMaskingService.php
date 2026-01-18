<?php

namespace App\Core\Services;

/**
 * Service for masking sensitive data in logs.
 */
class LogMaskingService
{
    /**
     * Fields that should be masked.
     *
     * @var array<string>
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'access_token',
        'refresh_token',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security_number',
        'bank_account',
        'routing_number',
    ];

    /**
     * Mask sensitive data in array.
     *
     * @param  array  $data
     * @return array
     */
    public function mask(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $masked[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $masked[$key] = $this->mask($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Check if field is sensitive.
     *
     * @param  string  $field
     * @return bool
     */
    protected function isSensitiveField(string $field): bool
    {
        $fieldLower = strtolower($field);

        foreach ($this->sensitiveFields as $sensitiveField) {
            if (str_contains($fieldLower, $sensitiveField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask a value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function maskValue(mixed $value): string
    {
        if (empty($value)) {
            return '***';
        }

        $length = strlen((string) $value);

        if ($length <= 4) {
            return '****';
        }

        // Show first 2 and last 2 characters, mask the rest
        return substr((string) $value, 0, 2) . str_repeat('*', $length - 4) . substr((string) $value, -2);
    }

    /**
     * Add custom sensitive field.
     *
     * @param  string  $field
     * @return void
     */
    public function addSensitiveField(string $field): void
    {
        if (! in_array($field, $this->sensitiveFields)) {
            $this->sensitiveFields[] = $field;
        }
    }
}

