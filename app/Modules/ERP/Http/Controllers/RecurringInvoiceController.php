<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\RecurringInvoice;
use App\Modules\ERP\Services\RecurringInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecurringInvoiceController extends Controller
{
    protected RecurringInvoiceService $recurringInvoiceService;

    public function __construct(RecurringInvoiceService $recurringInvoiceService)
    {
        $this->recurringInvoiceService = $recurringInvoiceService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RecurringInvoice::class);

        $query = RecurringInvoice::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $recurringInvoices = $query->latest()->paginate();

        return \App\Modules\ERP\Http\Resources\RecurringInvoiceResource::collection($recurringInvoices);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', RecurringInvoice::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:accounts,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'currency_id' => 'required|exists:currencies,id',
            'frequency' => 'required|string|in:daily,weekly,monthly,quarterly,yearly',
            'interval' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'occurrences' => 'nullable|integer|min:1',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'invoice_data' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['created_by'] = $request->user()->id;
        $validated['next_run_date'] = $validated['start_date'];
        $validated['is_active'] = $request->input('is_active', true);

        $recurringInvoice = RecurringInvoice::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Recurring invoice created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\RecurringInvoiceResource($recurringInvoice),
        ], 201);
    }

    public function show(RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorize('view', $recurringInvoice);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\RecurringInvoiceResource($recurringInvoice),
        ]);
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorize('update', $recurringInvoice);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'customer_id' => 'nullable|exists:accounts,id',
            'customer_name' => 'sometimes|required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_address' => 'nullable|string',
            'currency_id' => 'sometimes|required|exists:currencies,id',
            'frequency' => 'sometimes|required|string|in:daily,weekly,monthly,quarterly,yearly',
            'interval' => 'sometimes|required|integer|min:1',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after:start_date',
            'occurrences' => 'nullable|integer|min:1',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'invoice_data' => 'sometimes|required|array',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $recurringInvoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Recurring invoice updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\RecurringInvoiceResource($recurringInvoice->fresh()),
        ]);
    }

    public function destroy(RecurringInvoice $recurringInvoice): JsonResponse
    {
        $this->authorize('delete', $recurringInvoice);

        $recurringInvoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recurring invoice deleted successfully.',
        ]);
    }

    public function generateDueInvoices(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RecurringInvoice::class);

        $count = $this->recurringInvoiceService->generateDueInvoices();

        return response()->json([
            'success' => true,
            'message' => "Generated {$count} recurring invoices.",
            'data' => ['count' => $count],
        ]);
    }
}

