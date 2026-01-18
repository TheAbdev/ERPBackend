<?php

namespace App\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Services\TenantSettingsService;
use App\Core\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TenantSettingsController extends Controller
{
    protected TenantSettingsService $settingsService;
    protected TenantContext $tenantContext;

    public function __construct(
        TenantSettingsService $settingsService,
        TenantContext $tenantContext
    ) {
        $this->settingsService = $settingsService;
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get tenant settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $settings = $this->settingsService->getSettings();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update tenant settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'company_logo' => 'sometimes|string|nullable',
            'timezone' => 'sometimes|string|max:255',
            'language' => 'sometimes|string|max:10',
            'currency' => 'sometimes|string|max:10',
            'address' => 'sometimes|string|nullable',
            'phone' => 'sometimes|string|nullable|max:50',
            'email' => 'sometimes|email|nullable|max:255',
            'tax_id' => 'sometimes|string|nullable|max:100',
            'registration_number' => 'sometimes|string|nullable|max:100',
            'maintenance_mode' => 'sometimes|boolean',
            'session_timeout' => 'sometimes|integer|min:5|max:1440',
            'password_policy' => 'sometimes|array',
        ]);

        $settings = $this->settingsService->updateSettings($validated);

        return response()->json([
            'data' => $settings,
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
        $settings = $this->settingsService->getEmailSettings();

        return response()->json([
            'data' => $settings,
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
            'smtp_password' => 'sometimes|string|max:255',
            'smtp_encryption' => 'sometimes|string|in:tls,ssl',
            'from_email' => 'sometimes|email|max:255',
            'from_name' => 'sometimes|string|max:255',
        ]);

        $settings = $this->settingsService->updateEmailSettings($validated);

        return response()->json([
            'data' => $settings,
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
            'test_email' => 'required|email',
        ]);

        try {
            $this->settingsService->testEmail($validated['test_email']);

            return response()->json([
                'message' => 'Test email sent successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send test email: ' . $e->getMessage(),
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
        $settings = $this->settingsService->getStorageSettings();

        return response()->json([
            'data' => $settings,
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
            'storage_driver' => 'sometimes|string|in:local,s3',
            's3_key' => 'sometimes|string|nullable|max:255',
            's3_secret' => 'sometimes|string|nullable|max:255',
            's3_region' => 'sometimes|string|nullable|max:100',
            's3_bucket' => 'sometimes|string|nullable|max:255',
            's3_endpoint' => 'sometimes|string|nullable|url|max:255',
        ]);

        $settings = $this->settingsService->updateStorageSettings($validated);

        return response()->json([
            'data' => $settings,
            'message' => 'Storage settings updated successfully.',
        ]);
    }

    /**
     * Test S3 connection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testS3Connection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            's3_key' => 'required|string|max:255',
            's3_secret' => 'required|string|max:255',
            's3_region' => 'required|string|max:100',
            's3_bucket' => 'required|string|max:255',
            's3_endpoint' => 'sometimes|string|nullable|url|max:255',
        ]);

        try {
            $this->settingsService->testS3Connection($validated);

            return response()->json([
                'message' => 'S3 connection successful.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'S3 connection failed: ' . $e->getMessage(),
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
        $settings = $this->settingsService->getSecuritySettings();

        return response()->json([
            'data' => $settings,
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
            'two_factor_enabled' => 'sometimes|boolean',
            'rate_limiting_enabled' => 'sometimes|boolean',
            'rate_limit_requests' => 'sometimes|integer|min:1|max:10000',
            'rate_limit_period' => 'sometimes|integer|min:1|max:3600',
            'password_min_length' => 'sometimes|integer|min:6|max:128',
            'password_require_uppercase' => 'sometimes|boolean',
            'password_require_lowercase' => 'sometimes|boolean',
            'password_require_numbers' => 'sometimes|boolean',
            'password_require_symbols' => 'sometimes|boolean',
        ]);

        $settings = $this->settingsService->updateSecuritySettings($validated);

        return response()->json([
            'data' => $settings,
            'message' => 'Security settings updated successfully.',
        ]);
    }
}




