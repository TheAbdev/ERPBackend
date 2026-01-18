<?php

namespace App\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthController extends Controller
{
    /**
     * Enable 2FA for authenticated user.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->enableTwoFactorAuth();

        $qrCodeUrl = $user->getTwoFactorQrCode();
        $recoveryCodes = $user->getRecoveryCodes();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled. Please verify with a code.',
            'data' => [
                'qr_code_url' => $qrCodeUrl,
                'secret' => decrypt($user->two_factor_secret),
                'recovery_codes' => $recoveryCodes, // Show only once
            ],
        ]);
    }

    /**
     * Verify and confirm 2FA setup.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is not enabled.',
            ], 400);
        }

        if ($user->verifyTwoFactorCode($request->code)) {
            $user->update(['two_factor_confirmed_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Two-factor authentication verified and enabled.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid verification code.',
        ], 422);
    }

    /**
     * Disable 2FA for authenticated user.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password.',
            ], 422);
        }

        $user->disableTwoFactorAuth();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled.',
        ]);
    }

    /**
     * Get 2FA status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $user->two_factor_enabled,
                'confirmed' => $user->two_factor_confirmed_at !== null,
                'recovery_codes_count' => count($user->two_factor_recovery_codes ?? []),
            ],
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password.',
            ], 422);
        }

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is not enabled.',
            ], 400);
        }

        $recoveryCodes = $user->generateRecoveryCodes();
        $user->update(['two_factor_recovery_codes' => $recoveryCodes]);

        return response()->json([
            'success' => true,
            'message' => 'Recovery codes regenerated.',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }
}

