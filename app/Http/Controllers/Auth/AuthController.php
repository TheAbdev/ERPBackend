<?php

namespace App\Http\Controllers\Auth;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find user by email
        $user = \App\Models\User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        $loginSuccess = $user && Hash::check($request->password, $user->password);

        // Log failed login attempt
        if (! $loginSuccess) {
            try {
                \App\Core\Models\UserLoginHistory::create([
                    'tenant_id' => $user?->tenant_id,
                    'user_id' => $user?->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_type' => $this->detectDeviceType($request->userAgent()),
                    'browser' => $this->detectBrowser($request->userAgent()),
                    'platform' => $this->detectPlatform($request->userAgent()),
                    'success' => false,
                    'failure_reason' => 'Invalid credentials',
                    'logged_in_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Don't break login if logging fails
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (! $user->is_active) {
            // Log failed login attempt due to inactive account
            try {
                \App\Core\Models\UserLoginHistory::create([
                    'tenant_id' => $user->tenant_id,
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_type' => $this->detectDeviceType($request->userAgent()),
                    'browser' => $this->detectBrowser($request->userAgent()),
                    'platform' => $this->detectPlatform($request->userAgent()),
                    'success' => false,
                    'failure_reason' => 'Account is inactive',
                    'logged_in_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Don't break login if logging fails
            }

            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact your administrator.'],
            ]);
        }

        // Load user with tenant (including owner) and roles with permissions
        $user->load('tenant.owner', 'roles.permissions');

        // Check if user is Site Owner (has site_owner role or platform.manage permission)
        $isSiteOwner = $user->hasRole('site_owner') || $user->hasPermission('platform.manage');

        // Check if user is Tenant Owner (has super_admin role)
        $isTenantOwner = $user->hasRole('super_admin');

        // For non-Site Owner users, verify tenant exists and is active
        if (! $isSiteOwner) {
            // Use user's tenant_id automatically
            if (! $user->tenant_id) {
                return response()->json([
                    'message' => 'User does not belong to any tenant.',
                ], 400);
            }

            // Load tenant if not already loaded
            if (! $user->relationLoaded('tenant')) {
                $user->load('tenant');
            }

            // Verify tenant exists and is active
            if (! $user->tenant) {
                return response()->json([
                    'message' => 'Tenant not found.',
                ], 404);
            }

            if (! $user->tenant->isActive()) {
                return response()->json([
                    'message' => 'Tenant is not active.',
                ], 403);
            }
        }

        // Revoke all existing tokens for this user
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log authentication event
        try {
            app(AuditService::class)->logAuth('login', $user, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Log login history
            \App\Core\Models\UserLoginHistory::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_type' => $this->detectDeviceType($request->userAgent()),
                'browser' => $this->detectBrowser($request->userAgent()),
                'platform' => $this->detectPlatform($request->userAgent()),
                'success' => true,
                'logged_in_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't break login if audit logging fails
            \Illuminate\Support\Facades\Log::error('Failed to log login event', ['error' => $e->getMessage()]);
        }

        // Get user roles and permissions for frontend
        $roles = $user->roles()
            ->wherePivot('tenant_id', $user->tenant_id)
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                ];
            });

        $permissions = $user->getPermissions()
            ->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'module' => $permission->module ?? '',
                ];
            })
            ->values()
            ->toArray();

        // Log permissions for debugging
        \Illuminate\Support\Facades\Log::info('Login response permissions', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'roles_count' => $roles->count(),
            'permissions_count' => count($permissions),
            'permission_slugs' => array_column($permissions, 'slug'),
        ]);

        // Add flags to user object for frontend
        $userData = $user->toArray();
        $userData['is_site_owner'] = $isSiteOwner;
        $userData['is_tenant_owner'] = $isTenantOwner;

        return response()->json([
            'user' => $userData,
            'roles' => $roles,
            'permissions' => $permissions,
            'tenant_id' => $user->tenant_id,
            'token' => $token,
        ]);
    }

    /**
     * Handle a logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke the current token
        $user->currentAccessToken()->delete();

        // Log logout event
        try {
            app(AuditService::class)->logAuth('logout', $user, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Don't break logout if audit logging fails
            \Illuminate\Support\Facades\Log::error('Failed to log logout event', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('tenant.owner', 'roles.permissions');

        return response()->json([
            'user' => $user,
            'permissions' => $user->getPermissions(),
        ]);
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent.
     */
    private function detectBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/chrome/i', $userAgent) && ! preg_match('/edg/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/safari/i', $userAgent) && ! preg_match('/chrome/i', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/edg/i', $userAgent)) {
            return 'Edge';
        }
        if (preg_match('/opera|opr/i', $userAgent)) {
            return 'Opera';
        }

        return 'Unknown';
    }

    /**
     * Detect platform from user agent.
     */
    private function detectPlatform(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        if (preg_match('/windows/i', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/macintosh|mac os/i', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        }
        if (preg_match('/android/i', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        }

        return 'Unknown';
    }


    /**
     * Send password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Find user by email
        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user) {
            // Return success even if user doesn't exist (security best practice)
            return response()->json([
                'message' => 'If that email address exists in our system, we will send a password reset link.',
            ]);
        }

        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link has been sent to your email address.',
            ]);
        }

        return response()->json([
            'message' => 'Unable to send password reset link. Please try again later.',
        ], 500);
    }

    /**
     * Reset password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                // Revoke all tokens to force re-login
                $user->tokens()->delete();

                // Log password reset event
                try {
                    app(AuditService::class)->logAuth('password_reset', $user, [
                        'ip_address' => request()->ip(),
                    ]);
                } catch (\Exception $e) {
                    // Don't break reset if audit logging fails
                    \Illuminate\Support\Facades\Log::error('Failed to log password reset event', ['error' => $e->getMessage()]);
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully. You can now login with your new password.',
            ]);
        }

        return response()->json([
            'message' => 'Unable to reset password. The token may be invalid or expired.',
        ], 400);
    }
}
