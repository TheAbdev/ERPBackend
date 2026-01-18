<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Resources\EmailAccountResource;
use App\Modules\CRM\Models\EmailAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmailAccountController extends Controller
{
    /**
     * Display a listing of email accounts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EmailAccount::class);

        $accounts = EmailAccount::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate();

        return EmailAccountResource::collection($accounts);
    }

    /**
     * Store a newly created email account.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EmailAccount::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type' => 'required|in:smtp,imap',
            'credentials' => 'required|array',
            'is_active' => 'sometimes|boolean',
            'auto_sync' => 'sometimes|boolean',
            'settings' => 'sometimes|array',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = $request->input('is_active', true);
        $validated['auto_sync'] = $request->input('auto_sync', false);

        $account = EmailAccount::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Email account created successfully.',
            'data' => new EmailAccountResource($account),
        ], 201);
    }

    /**
     * Display the specified email account.
     */
    public function show(EmailAccount $emailAccount): JsonResponse
    {
        $this->authorize('view', $emailAccount);

        return response()->json([
            'success' => true,
            'data' => new EmailAccountResource($emailAccount),
        ]);
    }

    /**
     * Update the specified email account.
     */
    public function update(Request $request, EmailAccount $emailAccount): JsonResponse
    {
        $this->authorize('update', $emailAccount);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'type' => 'sometimes|required|in:smtp,imap',
            'is_active' => 'sometimes|boolean',
            'auto_sync' => 'sometimes|boolean',
            'credentials' => 'sometimes|array',
            'settings' => 'sometimes|array',
        ]);

        $emailAccount->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Email account updated successfully.',
            'data' => new EmailAccountResource($emailAccount->fresh()),
        ]);
    }

    /**
     * Remove the specified email account.
     */
    public function destroy(EmailAccount $emailAccount): JsonResponse
    {
        $this->authorize('delete', $emailAccount);

        $emailAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email account deleted successfully.',
        ]);
    }
}

