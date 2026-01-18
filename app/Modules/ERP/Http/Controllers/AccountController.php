<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\AccountResource;
use App\Modules\ERP\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Account::class);

        $query = Account::with(['parent', 'children'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $accounts = $query->orderBy('display_order')->orderBy('code')->paginate();

        return AccountResource::collection($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Account::class);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:accounts,id',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        $account = Account::create($validated);

        return response()->json([
            'message' => 'Account created successfully.',
            'data' => new AccountResource($account),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        return response()->json([
            'data' => new AccountResource($account->load(['parent', 'children'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:accounts,id',
            'code' => 'sometimes|required|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $account->update($validated);

        return response()->json([
            'message' => 'Account updated successfully.',
            'data' => new AccountResource($account),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Account $account): JsonResponse
    {
        $this->authorize('delete', $account);

        // Check if account has journal entry lines
        if ($account->journalEntryLines()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account with journal entry lines.',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }
}

