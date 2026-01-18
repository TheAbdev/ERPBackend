<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Account;
use App\Modules\CRM\Services\AccountMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountMergeController extends Controller
{
    protected AccountMergeService $mergeService;

    public function __construct(AccountMergeService $mergeService)
    {
        $this->mergeService = $mergeService;
    }

    /**
     * Merge accounts.
     */
    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'target_account_id' => 'required|exists:accounts,id',
            'source_account_id' => 'required|exists:accounts,id',
        ]);

        $targetAccount = Account::findOrFail($request->target_account_id);
        $sourceAccount = Account::findOrFail($request->source_account_id);

        $this->authorize('update', $targetAccount);
        $this->authorize('delete', $sourceAccount);

        $mergedAccount = $this->mergeService->merge($targetAccount, $sourceAccount);

        return response()->json([
            'success' => true,
            'message' => 'Accounts merged successfully.',
            'data' => $mergedAccount,
        ]);
    }
}

