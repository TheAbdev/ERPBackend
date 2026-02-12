<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Core\Models\Tenant;
use App\Core\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\PostJournalEntryRequest;
use App\Modules\ERP\Http\Requests\StoreJournalEntryRequest;
use App\Modules\ERP\Http\Requests\UpdateJournalEntryRequest;
use App\Modules\ERP\Http\Resources\JournalEntryResource;
use App\Modules\ERP\Models\JournalEntry;
use App\Modules\ERP\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JournalEntryController extends Controller
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', JournalEntry::class);

        $query = JournalEntry::with(['fiscalYear', 'fiscalPeriod', 'creator', 'poster', 'lines.account', 'lines.currency'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('fiscal_period_id')) {
            $query->where('fiscal_period_id', $request->input('fiscal_period_id'));
        }

        if ($request->has('account_id')) {
            $query->whereHas('lines', fn ($q) => $q->where('account_id', $request->input('account_id')));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('entry_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('entry_date', '<=', $request->input('date_to'));
        }

        $entries = $query->latest('entry_date')->paginate();

        return JournalEntryResource::collection($entries);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StoreJournalEntryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $this->authorize('create', JournalEntry::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        $entry = JournalEntry::create($validated);

        // Create journal entry lines
        foreach ($request->input('lines', []) as $lineData) {
            // Normalize line data: convert debit_amount/credit_amount to debit/credit
            $normalizedLine = [
                'account_id' => $lineData['account_id'],
                'currency_id' => $lineData['currency_id'] ?? null,
                'debit' => $lineData['debit'] ?? $lineData['debit_amount'] ?? 0,
                'credit' => $lineData['credit'] ?? $lineData['credit_amount'] ?? 0,
                'description' => $lineData['description'] ?? null,
                'line_number' => $lineData['line_number'] ?? null,
            ];
            $entry->lines()->create($normalizedLine);
        }

        return response()->json([
            'message' => 'Journal entry created successfully.',
            'data' => new JournalEntryResource($entry->load(['fiscalYear', 'fiscalPeriod', 'lines.account', 'lines.currency'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\JournalEntry  $journalEntry
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('view', $journalEntry);

        return response()->json([
            'data' => new JournalEntryResource($journalEntry->load(['fiscalYear', 'fiscalPeriod', 'creator', 'poster', 'lines.account', 'lines.currency', 'reference'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\UpdateJournalEntryRequest  $request
     * @param  \App\Modules\ERP\Models\JournalEntry  $journalEntry
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('update', $journalEntry);

        if ($journalEntry->isPosted()) {
            return response()->json([
                'message' => 'Cannot update a posted journal entry.',
            ], 422);
        }

        $journalEntry->update($request->validated());

        // Update lines if provided
        if ($request->has('lines')) {
            $journalEntry->lines()->delete();
            foreach ($request->input('lines', []) as $lineData) {
                // Normalize line data: convert debit_amount/credit_amount to debit/credit
                $normalizedLine = [
                    'account_id' => $lineData['account_id'],
                    'currency_id' => $lineData['currency_id'] ?? null,
                    'debit' => $lineData['debit'] ?? $lineData['debit_amount'] ?? 0,
                    'credit' => $lineData['credit'] ?? $lineData['credit_amount'] ?? 0,
                    'description' => $lineData['description'] ?? null,
                    'line_number' => $lineData['line_number'] ?? null,
                ];
                $journalEntry->lines()->create($normalizedLine);
            }
        }

        return response()->json([
            'message' => 'Journal entry updated successfully.',
            'data' => new JournalEntryResource($journalEntry->load(['fiscalYear', 'fiscalPeriod', 'lines.account', 'lines.currency'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\JournalEntry  $journalEntry
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('delete', $journalEntry);

        if ($journalEntry->isPosted()) {
            return response()->json([
                'message' => 'Cannot delete a posted journal entry.',
            ], 422);
        }

        $journalEntry->delete();

        return response()->json([
            'message' => 'Journal entry deleted successfully.',
        ]);
    }

    /**
     * Post a journal entry.
     *
     * @param  \App\Modules\ERP\Http\Requests\PostJournalEntryRequest  $request
     * @param  \App\Modules\ERP\Models\JournalEntry  $journalEntry
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(PostJournalEntryRequest $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorize('update', $journalEntry);

        // Ensure AccountingService uses the same tenant as the journal entry.
        $tenantContext = app(TenantContext::class);
        if (! $tenantContext->hasTenant() || (int) $tenantContext->getTenantId() !== (int) $journalEntry->tenant_id) {
            $tenant = Tenant::find($journalEntry->tenant_id);
            if ($tenant) {
                $tenantContext->setTenant($tenant);
                $request->attributes->set('tenant_id', $tenant->id);
            }
        }

        try {
            $this->accountingService->postJournalEntry($journalEntry, $request->user()->id);

            return response()->json([
                'message' => 'Journal entry posted successfully.',
                'data' => new JournalEntryResource($journalEntry->fresh()->load(['fiscalYear', 'fiscalPeriod', 'poster', 'lines.account', 'lines.currency'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}





