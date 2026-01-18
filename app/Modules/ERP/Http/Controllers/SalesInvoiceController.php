<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\IssueInvoiceRequest;
use App\Modules\ERP\Http\Requests\StoreSalesInvoiceRequest;
use App\Modules\ERP\Http\Requests\UpdateSalesInvoiceRequest;
use App\Modules\ERP\Http\Resources\SalesInvoiceResource;
use App\Modules\ERP\Models\SalesInvoice;
use App\Modules\ERP\Services\AccountingService;
use App\Modules\ERP\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    protected InvoiceService $invoiceService;
    protected AccountingService $accountingService;

    public function __construct(
        InvoiceService $invoiceService,
        AccountingService $accountingService
    ) {
        $this->invoiceService = $invoiceService;
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalesInvoice::class);

        $query = SalesInvoice::with(['salesOrder', 'fiscalYear', 'fiscalPeriod', 'currency', 'creator', 'issuer'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('issue_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('issue_date', '<=', $request->input('date_to'));
        }

        if ($request->has('customer_name')) {
            $query->where('customer_name', 'like', '%' . $request->input('customer_name') . '%');
        }

        $invoices = $query->latest('issue_date')->paginate();

        return SalesInvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StoreSalesInvoiceRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSalesInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', SalesInvoice::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['tenant_id'] = $request->user()->tenant_id;
        
        // Get fiscal year and period from issue_date if not provided
        $fiscalYear = $this->accountingService->getActiveFiscalYear($validated['issue_date']);
        $validated['fiscal_year_id'] = $fiscalYear->id;
        
        // If fiscal_period_id is not provided, get it from issue_date
        if (empty($validated['fiscal_period_id'])) {
            $fiscalPeriod = $this->accountingService->getActiveFiscalPeriod($validated['issue_date']);
            $validated['fiscal_period_id'] = $fiscalPeriod->id;
        }

        $invoice = DB::transaction(function () use ($validated, $request) {
            $invoice = SalesInvoice::create($validated);

            // Create items
            foreach ($request->input('items', []) as $index => $itemData) {
                $itemData['tenant_id'] = $validated['tenant_id'];
                $itemData['line_number'] = $index + 1;
                $invoice->items()->create($itemData);
            }

            return $invoice->load('items');
        });

        return response()->json([
            'message' => 'Sales invoice created successfully.',
            'data' => new SalesInvoiceResource($invoice),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\SalesInvoice  $salesInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(SalesInvoice $salesInvoice): JsonResponse
    {
        $this->authorize('view', $salesInvoice);

        $salesInvoice->load(['salesOrder', 'fiscalYear', 'fiscalPeriod', 'currency', 'creator', 'issuer', 'items.product', 'items.productVariant', 'paymentAllocations.payment']);

        return response()->json([
            'data' => new SalesInvoiceResource($salesInvoice),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\UpdateSalesInvoiceRequest  $request
     * @param  \App\Modules\ERP\Models\SalesInvoice  $salesInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSalesInvoiceRequest $request, SalesInvoice $salesInvoice): JsonResponse
    {
        $this->authorize('update', $salesInvoice);

        if (!$salesInvoice->canBeEdited()) {
            return response()->json([
                'message' => 'Cannot update invoice. Only draft invoices can be edited.',
            ], 422);
        }

        $validated = $request->validated();

        $salesInvoice = DB::transaction(function () use ($salesInvoice, $validated, $request) {
            $salesInvoice->update($validated);

            // Update items if provided
            if ($request->has('items')) {
                $salesInvoice->items()->delete();
                foreach ($request->input('items', []) as $index => $itemData) {
                    $itemData['tenant_id'] = $salesInvoice->tenant_id;
                    $itemData['line_number'] = $index + 1;
                    $salesInvoice->items()->create($itemData);
                }
            }

            return $salesInvoice->fresh()->load('items');
        });

        return response()->json([
            'message' => 'Sales invoice updated successfully.',
            'data' => new SalesInvoiceResource($salesInvoice),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\SalesInvoice  $salesInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SalesInvoice $salesInvoice): JsonResponse
    {
        $this->authorize('delete', $salesInvoice);

        if (!$salesInvoice->canBeEdited()) {
            return response()->json([
                'message' => 'Cannot delete invoice. Only draft invoices can be deleted.',
            ], 422);
        }

        $salesInvoice->delete();

        return response()->json([
            'message' => 'Sales invoice deleted successfully.',
        ]);
    }

    /**
     * Issue the invoice.
     *
     * @param  \App\Modules\ERP\Http\Requests\IssueInvoiceRequest  $request
     * @param  \App\Modules\ERP\Models\SalesInvoice  $salesInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function issue(IssueInvoiceRequest $request, SalesInvoice $salesInvoice): JsonResponse
    {
        $this->authorize('update', $salesInvoice);

        try {
            $this->invoiceService->issueInvoice($salesInvoice, $request->user()->id);

            return response()->json([
                'message' => 'Invoice issued successfully.',
                'data' => new SalesInvoiceResource($salesInvoice->fresh()->load(['fiscalYear', 'fiscalPeriod', 'issuer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel the invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\SalesInvoice  $salesInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, SalesInvoice $salesInvoice): JsonResponse
    {
        $this->authorize('update', $salesInvoice);

        try {
            $this->invoiceService->cancelInvoice($salesInvoice, $request->user()->id);

            return response()->json([
                'message' => 'Invoice cancelled successfully.',
                'data' => new SalesInvoiceResource($salesInvoice->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

