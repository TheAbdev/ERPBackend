<?php

namespace App\Tenant\Services;

use App\Core\Models\Tenant;
use App\Core\Services\TenantContext;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TenantSettingsService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get current tenant.
     *
     * @return Tenant
     */
    protected function getTenant(): Tenant
    {
        $tenantId = $this->tenantContext->getTenantId();
        return Tenant::findOrFail($tenantId);
    }

    /**
     * Get tenant settings.
     *
     * @return array
     */
    public function getSettings(): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        return [
            'company_name' => $settings['company_name'] ?? $tenant->name,
            'company_logo' => $settings['company_logo'] ?? null,
            'timezone' => $settings['timezone'] ?? config('app.timezone', 'UTC'),
            'language' => $settings['language'] ?? config('app.locale', 'en'),
            'currency' => $settings['currency'] ?? 'USD',
            'address' => $settings['address'] ?? null,
            'phone' => $settings['phone'] ?? null,
            'email' => $settings['email'] ?? null,
            'tax_id' => $settings['tax_id'] ?? null,
            'registration_number' => $settings['registration_number'] ?? null,
            'maintenance_mode' => $settings['maintenance_mode'] ?? false,
            'session_timeout' => $settings['session_timeout'] ?? 120,
            'password_policy' => $settings['password_policy'] ?? [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => false,
            ],
        ];
    }

    /**
     * Update tenant settings.
     *
     * @param  array  $data
     * @return array
     */
    public function updateSettings(array $data): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        // Update settings
        foreach ($data as $key => $value) {
            $settings[$key] = $value;
        }

        $tenant->update(['settings' => $settings]);

        return $this->getSettings();
    }

    /**
     * Get email settings.
     *
     * @return array
     */
    public function getEmailSettings(): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        return [
            'smtp_host' => $settings['email']['smtp_host'] ?? config('mail.mailers.smtp.host', ''),
            'smtp_port' => $settings['email']['smtp_port'] ?? config('mail.mailers.smtp.port', 587),
            'smtp_username' => $settings['email']['smtp_username'] ?? config('mail.mailers.smtp.username', ''),
            'smtp_password' => '', // Never return password
            'smtp_encryption' => $settings['email']['smtp_encryption'] ?? config('mail.mailers.smtp.encryption', 'tls'),
            'from_email' => $settings['email']['from_email'] ?? config('mail.from.address', ''),
            'from_name' => $settings['email']['from_name'] ?? config('mail.from.name', ''),
        ];
    }

    /**
     * Update email settings.
     *
     * @param  array  $data
     * @return array
     */
    public function updateEmailSettings(array $data): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        if (!isset($settings['email'])) {
            $settings['email'] = [];
        }

        foreach ($data as $key => $value) {
            $settings['email'][$key] = $value;
        }

        $tenant->update(['settings' => $settings]);

        return $this->getEmailSettings();
    }

    /**
     * Test email configuration.
     *
     * @param  string  $testEmail
     * @return void
     * @throws \Exception
     */
    public function testEmail(string $testEmail): void
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];
        $emailSettings = $settings['email'] ?? [];

        // Temporarily override mail config
        if (!empty($emailSettings)) {
            Config::set('mail.mailers.smtp.host', $emailSettings['smtp_host'] ?? config('mail.mailers.smtp.host'));
            Config::set('mail.mailers.smtp.port', $emailSettings['smtp_port'] ?? config('mail.mailers.smtp.port'));
            Config::set('mail.mailers.smtp.username', $emailSettings['smtp_username'] ?? config('mail.mailers.smtp.username'));
            Config::set('mail.mailers.smtp.password', $emailSettings['smtp_password'] ?? config('mail.mailers.smtp.password'));
            Config::set('mail.mailers.smtp.encryption', $emailSettings['smtp_encryption'] ?? config('mail.mailers.smtp.encryption'));
            Config::set('mail.from.address', $emailSettings['from_email'] ?? config('mail.from.address'));
            Config::set('mail.from.name', $emailSettings['from_name'] ?? config('mail.from.name'));
        }

        try {
            Mail::raw('This is a test email from your tenant settings.', function ($message) use ($testEmail, $emailSettings) {
                $message->to($testEmail)
                    ->subject('Test Email from Tenant Settings')
                    ->from(
                        $emailSettings['from_email'] ?? config('mail.from.address'),
                        $emailSettings['from_name'] ?? config('mail.from.name')
                    );
            });
        } catch (\Exception $e) {
            throw new \Exception('Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Get storage settings.
     *
     * @return array
     */
    public function getStorageSettings(): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        return [
            'storage_driver' => $settings['storage']['driver'] ?? config('filesystems.default', 'local'),
            's3_key' => $settings['storage']['s3_key'] ?? config('filesystems.disks.s3.key', ''),
            's3_secret' => '', // Never return secret
            's3_region' => $settings['storage']['s3_region'] ?? config('filesystems.disks.s3.region', ''),
            's3_bucket' => $settings['storage']['s3_bucket'] ?? config('filesystems.disks.s3.bucket', ''),
            's3_endpoint' => $settings['storage']['s3_endpoint'] ?? config('filesystems.disks.s3.endpoint', ''),
        ];
    }

    /**
     * Update storage settings.
     *
     * @param  array  $data
     * @return array
     */
    public function updateStorageSettings(array $data): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        if (!isset($settings['storage'])) {
            $settings['storage'] = [];
        }

        // Map frontend keys to storage keys
        $mapping = [
            'storage_driver' => 'driver',
            's3_key' => 's3_key',
            's3_secret' => 's3_secret',
            's3_region' => 's3_region',
            's3_bucket' => 's3_bucket',
            's3_endpoint' => 's3_endpoint',
        ];

        foreach ($data as $key => $value) {
            if (isset($mapping[$key])) {
                $settings['storage'][$mapping[$key]] = $value;
            }
        }

        $tenant->update(['settings' => $settings]);

        return $this->getStorageSettings();
    }

    /**
     * Test S3 connection.
     *
     * @param  array  $data
     * @return void
     * @throws \Exception
     */
    public function testS3Connection(array $data): void
    {
        try {
            // Temporarily configure S3
            Config::set('filesystems.disks.s3.key', $data['s3_key']);
            Config::set('filesystems.disks.s3.secret', $data['s3_secret']);
            Config::set('filesystems.disks.s3.region', $data['s3_region']);
            Config::set('filesystems.disks.s3.bucket', $data['s3_bucket']);
            if (isset($data['s3_endpoint'])) {
                Config::set('filesystems.disks.s3.endpoint', $data['s3_endpoint']);
            }

            // Test connection by listing bucket
            Storage::disk('s3')->files('/', true);
        } catch (\Exception $e) {
            throw new \Exception('S3 connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get security settings.
     *
     * @return array
     */
    public function getSecuritySettings(): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        return [
            'two_factor_enabled' => $settings['security']['two_factor_enabled'] ?? false,
            'rate_limiting_enabled' => $settings['security']['rate_limiting_enabled'] ?? true,
            'rate_limit_requests' => $settings['security']['rate_limit_requests'] ?? 60,
            'rate_limit_period' => $settings['security']['rate_limit_period'] ?? 60,
            'password_min_length' => $settings['security']['password_min_length'] ?? 8,
            'password_require_uppercase' => $settings['security']['password_require_uppercase'] ?? true,
            'password_require_lowercase' => $settings['security']['password_require_lowercase'] ?? true,
            'password_require_numbers' => $settings['security']['password_require_numbers'] ?? true,
            'password_require_symbols' => $settings['security']['password_require_symbols'] ?? false,
        ];
    }

    /**
     * Update security settings.
     *
     * @param  array  $data
     * @return array
     */
    public function updateSecuritySettings(array $data): array
    {
        $tenant = $this->getTenant();
        $settings = $tenant->settings ?? [];

        if (!isset($settings['security'])) {
            $settings['security'] = [];
        }

        foreach ($data as $key => $value) {
            $settings['security'][$key] = $value;
        }

        $tenant->update(['settings' => $settings]);

        return $this->getSecuritySettings();
    }
}




