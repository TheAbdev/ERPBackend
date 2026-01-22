<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PlatformSettingsController extends Controller
{
    /**
     * Get platform settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'platform_name' => config('app.name', 'SaaS CRM-ERP'),
                'default_timezone' => config('app.timezone', 'UTC'),
                'default_language' => config('app.locale', 'en'),
                'maintenance_mode' => app()->isDownForMaintenance(),
                'platform_logo' => null,
                'favicon' => null,
            ],
        ]);
    }

    /**
     * Update platform settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform_name' => 'sometimes|string|max:255',
            'default_timezone' => 'sometimes|string|max:255',
            'default_language' => 'sometimes|string|max:10',
            'maintenance_mode' => 'sometimes|boolean',
        ]);

        // Update config (in production, store in database)
        if (isset($validated['platform_name'])) {
            Config::set('app.name', $validated['platform_name']);
        }
        if (isset($validated['default_timezone'])) {
            Config::set('app.timezone', $validated['default_timezone']);
        }
        if (isset($validated['default_language'])) {
            Config::set('app.locale', $validated['default_language']);
        }

        return response()->json([
            'data' => [
                'platform_name' => config('app.name'),
                'default_timezone' => config('app.timezone'),
                'default_language' => config('app.locale'),
                'maintenance_mode' => app()->isDownForMaintenance(),
            ],
            'message' => 'Settings updated successfully.',
        ]);
    }

    /**
     * Get email settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEmail(): JsonResponse
    {
        return response()->json([
            'data' => [
                'smtp_host' => config('mail.mailers.smtp.host', ''),
                'smtp_port' => config('mail.mailers.smtp.port', 587),
                'smtp_username' => config('mail.mailers.smtp.username', ''),
                'smtp_password' => '', // Never return password
                'smtp_encryption' => config('mail.mailers.smtp.encryption', 'tls'),
                'from_email' => config('mail.from.address', ''),
                'from_name' => config('mail.from.name', ''),
            ],
        ]);
    }

    /**
     * Update email settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'smtp_host' => 'sometimes|string|max:255',
            'smtp_port' => 'sometimes|integer|min:1|max:65535',
            'smtp_username' => 'sometimes|string|max:255',
            'smtp_password' => 'sometimes|string',
            'smtp_encryption' => 'sometimes|in:tls,ssl,none',
            'from_email' => 'sometimes|email|max:255',
            'from_name' => 'sometimes|string|max:255',
        ]);

        // Update config (in production, store in database)
        if (isset($validated['smtp_host'])) {
            Config::set('mail.mailers.smtp.host', $validated['smtp_host']);
        }
        if (isset($validated['smtp_port'])) {
            Config::set('mail.mailers.smtp.port', $validated['smtp_port']);
        }
        if (isset($validated['smtp_username'])) {
            Config::set('mail.mailers.smtp.username', $validated['smtp_username']);
        }
        if (isset($validated['smtp_password'])) {
            Config::set('mail.mailers.smtp.password', $validated['smtp_password']);
        }
        if (isset($validated['smtp_encryption'])) {
            Config::set('mail.mailers.smtp.encryption', $validated['smtp_encryption']);
        }
        if (isset($validated['from_email'])) {
            Config::set('mail.from.address', $validated['from_email']);
        }
        if (isset($validated['from_name'])) {
            Config::set('mail.from.name', $validated['from_name']);
        }

        return response()->json([
            'data' => [
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'smtp_username' => config('mail.mailers.smtp.username'),
                'smtp_encryption' => config('mail.mailers.smtp.encryption'),
                'from_email' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'message' => 'Email settings updated successfully.',
        ]);
    }

    /**
     * Test email configuration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to' => 'required|email',
        ]);

        try {
            Mail::raw('This is a test email from the platform.', function ($message) use ($validated) {
                $message->to($validated['to'])
                    ->subject('Platform Test Email');
            });

            return response()->json([
                'data' => [
                    'success' => true,
                    'message' => 'Test email sent successfully.',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [
                    'success' => false,
                    'message' => 'Failed to send test email: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get storage settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStorage(): JsonResponse
    {
        return response()->json([
            'data' => [
                'storage_driver' => config('filesystems.default', 'local'),
                'storage_path' => storage_path('app'),
                'max_file_size' => config('filesystems.max_file_size', 10240),
                's3_bucket' => config('filesystems.disks.s3.bucket', ''),
                's3_region' => config('filesystems.disks.s3.region', ''),
                's3_access_key_id' => '', // Never return sensitive data
                's3_secret_access_key' => '', // Never return sensitive data
                's3_endpoint' => config('filesystems.disks.s3.endpoint', ''),
            ],
        ]);
    }

    /**
     * Update storage settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStorage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'storage_driver' => 'sometimes|in:local,s3',
            'storage_path' => 'sometimes|string|max:255',
            'max_file_size' => 'sometimes|integer|min:1',
            's3_bucket' => 'sometimes|string|max:255',
            's3_region' => 'sometimes|string|max:255',
            's3_access_key_id' => 'sometimes|string|max:255',
            's3_secret_access_key' => 'sometimes|string',
            's3_endpoint' => 'sometimes|string|max:255',
        ]);

        // Update config (in production, store in database)
        if (isset($validated['storage_driver'])) {
            Config::set('filesystems.default', $validated['storage_driver']);
        }
        if (isset($validated['s3_bucket'])) {
            Config::set('filesystems.disks.s3.bucket', $validated['s3_bucket']);
        }
        if (isset($validated['s3_region'])) {
            Config::set('filesystems.disks.s3.region', $validated['s3_region']);
        }
        if (isset($validated['s3_access_key_id'])) {
            Config::set('filesystems.disks.s3.key', $validated['s3_access_key_id']);
        }
        if (isset($validated['s3_secret_access_key'])) {
            Config::set('filesystems.disks.s3.secret', $validated['s3_secret_access_key']);
        }
        if (isset($validated['s3_endpoint'])) {
            Config::set('filesystems.disks.s3.endpoint', $validated['s3_endpoint']);
        }

        return response()->json([
            'data' => [
                'storage_driver' => config('filesystems.default'),
                'storage_path' => storage_path('app'),
                'max_file_size' => config('filesystems.max_file_size', 10240),
                's3_bucket' => config('filesystems.disks.s3.bucket', ''),
                's3_region' => config('filesystems.disks.s3.region', ''),
                's3_endpoint' => config('filesystems.disks.s3.endpoint', ''),
            ],
            'message' => 'Storage settings updated successfully.',
        ]);
    }

    /**
     * Test S3 connection.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testS3(): JsonResponse
    {
        try {
            $disk = Storage::disk('s3');
            $disk->put('test.txt', 'test');
            $disk->delete('test.txt');

            return response()->json([
                'data' => [
                    'success' => true,
                    'message' => 'S3 connection test successful.',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [
                    'success' => false,
                    'message' => 'S3 connection test failed: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get security settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSecurity(): JsonResponse
    {
        return response()->json([
            'data' => [
                'password_min_length' => config('auth.password_min_length', 8),
                'password_require_uppercase' => config('auth.password_require_uppercase', true),
                'password_require_lowercase' => config('auth.password_require_lowercase', true),
                'password_require_numbers' => config('auth.password_require_numbers', true),
                'password_require_special_chars' => config('auth.password_require_special_chars', false),
                'session_timeout' => config('session.lifetime', 120),
                'remember_me_duration' => config('auth.remember_me_duration', 30),
                'require_2fa_site_owners' => config('auth.require_2fa_site_owners', false),
                'require_2fa_all_users' => config('auth.require_2fa_all_users', false),
                'api_rate_limit' => config('api.rate_limit', 60),
                'login_attempts_limit' => config('auth.login_attempts_limit', 5),
            ],
        ]);
    }

    /**
     * Update security settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSecurity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password_min_length' => 'sometimes|integer|min:6|max:32',
            'password_require_uppercase' => 'sometimes|boolean',
            'password_require_lowercase' => 'sometimes|boolean',
            'password_require_numbers' => 'sometimes|boolean',
            'password_require_special_chars' => 'sometimes|boolean',
            'session_timeout' => 'sometimes|integer|min:1',
            'remember_me_duration' => 'sometimes|integer|min:1',
            'require_2fa_site_owners' => 'sometimes|boolean',
            'require_2fa_all_users' => 'sometimes|boolean',
            'api_rate_limit' => 'sometimes|integer|min:1',
            'login_attempts_limit' => 'sometimes|integer|min:1',
        ]);

        // Update config (in production, store in database)
        foreach ($validated as $key => $value) {
            Config::set("auth.{$key}", $value);
        }

        return response()->json([
            'data' => [
                'password_min_length' => config('auth.password_min_length', 8),
                'password_require_uppercase' => config('auth.password_require_uppercase', true),
                'password_require_lowercase' => config('auth.password_require_lowercase', true),
                'password_require_numbers' => config('auth.password_require_numbers', true),
                'password_require_special_chars' => config('auth.password_require_special_chars', false),
                'session_timeout' => config('session.lifetime', 120),
                'remember_me_duration' => config('auth.remember_me_duration', 30),
                'require_2fa_site_owners' => config('auth.require_2fa_site_owners', false),
                'require_2fa_all_users' => config('auth.require_2fa_all_users', false),
                'api_rate_limit' => config('api.rate_limit', 60),
                'login_attempts_limit' => config('auth.login_attempts_limit', 5),
            ],
            'message' => 'Security settings updated successfully.',
        ]);
    }
}














