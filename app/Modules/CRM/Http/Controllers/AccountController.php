<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\StoreAccountRequest;
use App\Modules\CRM\Http\Requests\UpdateAccountRequest;
use App\Modules\CRM\Http\Resources\AccountResource;
use App\Modules\CRM\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Account::class);

        $accounts = Account::with(['creator', 'parent', 'children', 'contacts'])
            ->latest()
            ->paginate();

        return AccountResource::collection($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StoreAccountRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $this->authorize('create', Account::class);

        $account = Account::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        // Attach contacts if provided
        if ($request->has('contact_ids')) {
            $account->contacts()->attach($request->contact_ids, [
                'tenant_id' => $request->user()->tenant_id,
            ]);
        }

        $account->load(['creator', 'parent', 'children', 'contacts']);

        return response()->json([
            'data' => new AccountResource($account),
            'message' => 'Account created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Account $account): JsonResponse
    {
        $this->authorize('view', $account);

        $account->load(['creator', 'parent', 'children', 'contacts']);

        return response()->json([
            'data' => new AccountResource($account),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdateAccountRequest  $request
     * @param  \App\Modules\CRM\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $account->update($request->validated());
        $account->load(['creator', 'parent', 'children', 'contacts']);

        return response()->json([
            'data' => new AccountResource($account),
            'message' => 'Account updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Account $account): JsonResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ]);
    }

    /**
     * Attach contacts to the account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\CRM\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachContacts(\Illuminate\Http\Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'contact_ids' => ['required', 'array'],
            'contact_ids.*' => ['exists:contacts,id'],
        ]);

        foreach ($request->contact_ids as $contactId) {
            if (! $account->contacts()->where('contacts.id', $contactId)->exists()) {
                $account->contacts()->attach($contactId, ['tenant_id' => $request->user()->tenant_id]);
            }
        }

        $account->load('contacts');

        return response()->json([
            'data' => new AccountResource($account),
            'message' => 'Contacts attached successfully.',
        ]);
    }

    /**
     * Detach contacts from the account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\CRM\Models\Account  $account
     * @return \Illuminate\Http\JsonResponse
     */
    public function detachContacts(\Illuminate\Http\Request $request, Account $account): JsonResponse
    {
        $this->authorize('update', $account);

        $request->validate([
            'contact_ids' => ['required', 'array'],
            'contact_ids.*' => ['exists:contacts,id'],
        ]);

        $account->contacts()->detach($request->contact_ids);

        $account->load('contacts');

        return response()->json([
            'data' => new AccountResource($account),
            'message' => 'Contacts detached successfully.',
        ]);
    }
}

