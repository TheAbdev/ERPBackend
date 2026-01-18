<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Requests\IssueInvoiceRequest;
use App\Modules\ERP\Http\Requests\StorePurchaseInvoiceRequest;
use App\Modules\ERP\Http\Requests\UpdateSalesInvoiceRequest;
use App\Modules\ERP\Http\Resources\PurchaseInvoiceResource;
use App\Modules\ERP\Models\PurchaseInvoice;
use App\Modules\ERP\Services\AccountingService;
use App\Modules\ERP\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
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
        $this->authorize('viewAny', PurchaseInvoice::class);

        $query = PurchaseInvoice::with(['purchaseOrder', 'fiscalYear', 'fiscalPeriod', 'currency', 'creator', 'issuer'])
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

        if ($request->has('supplier_name')) {
            $query->where('supplier_name', 'like', '%' . $request->input('supplier_name') . '%');
        }

        $invoices = $query->latest('issue_date')->paginate();

        return PurchaseInvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StorePurchaseInvoiceRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePurchaseInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseInvoice::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['fiscal_year_id'] = $this->accountingService->getActiveFiscalYear($validated['issue_date'])->id;

        $invoice = DB::transaction(function () use ($validated, $request) {
            $invoice = PurchaseInvoice::create($validated);

            // Create items
            foreach ($request->input('items', []) as $index => $itemData) {
                $itemData['tenant_id'] = $validated['tenant_id'];
                $itemData['line_number'] = $index + 1;
                $invoice->items()->create($itemData);
            }

            return $invoice->load('items');
        });

        return response()->json([
            'message' => 'Purchase invoice created successfully.',
            'data' => new PurchaseInvoiceResource($invoice),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\PurchaseInvoice  $purchaseInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $this->authorize('view', $purchaseInvoice);

        $purchaseInvoice->load(['purchaseOrder', 'fiscalYear', 'fiscalPeriod', 'currency', 'creator', 'issuer', 'items.product', 'items.productVariant', 'paymentAllocations.payment']);

        return response()->json([
            'data' => new PurchaseInvoiceResource($purchaseInvoice),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\UpdateSalesInvoiceRequest  $request
     * @param  \App\Modules\ERP\Models\PurchaseInvoice  $purchaseInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSalesInvoiceRequest $request, PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $this->authorize('update', $purchaseInvoice);

        if (!$purchaseInvoice->canBeEdited()) {
            return response()->json([
                'message' => 'Cannot update invoice. Only draft invoices can be edited.',
            ], 422);
        }

        $validated = $request->validated();

        $purchaseInvoice = DB::transaction(function () use ($purchaseInvoice, $validated, $request) {
            $purchaseInvoice->update($validated);

            // Update items if provided
            if ($request->has('items')) {
                $purchaseInvoice->items()->delete();
                foreach ($request->input('items', []) as $index => $itemData) {
                    $itemData['tenant_id'] = $purchaseInvoice->tenant_id;
                    $itemData['line_number'] = $index + 1;
                    $purchaseInvoice->items()->create($itemData);
                }
            }

            return $purchaseInvoice->fresh()->load('items');
        });

        return response()->json([
            'message' => 'Purchase invoice updated successfully.',
            'data' => new PurchaseInvoiceResource($purchaseInvoice),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\PurchaseInvoice  $purchaseInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $this->authorize('delete', $purchaseInvoice);

        if (!$purchaseInvoice->canBeEdited()) {
            return response()->json([
                'message' => 'Cannot delete invoice. Only draft invoices can be deleted.',
            ], 422);
        }

        $purchaseInvoice->delete();

        return response()->json([
            'message' => 'Purchase invoice deleted successfully.',
        ]);
    }

    /**
     * Issue the invoice.
     *
     * @param  \App\Modules\ERP\Http\Requests\IssueInvoiceRequest  $request
     * @param  \App\Modules\ERP\Models\PurchaseInvoice  $purchaseInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function issue(IssueInvoiceRequest $request, PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $this->authorize('update', $purchaseInvoice);

        try {
            $this->invoiceService->issuePurchaseInvoice($purchaseInvoice, $request->user()->id);

            return response()->json([
                'message' => 'Invoice issued successfully.',
                'data' => new PurchaseInvoiceResource($purchaseInvoice->fresh()->load(['fiscalYear', 'fiscalPeriod', 'issuer'])),
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
     * @param  \App\Modules\ERP\Models\PurchaseInvoice  $purchaseInvoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, PurchaseInvoice $purchaseInvoice): JsonResponse
    {
        $this->authorize('update', $purchaseInvoice);

        try {
            $this->invoiceService->cancelInvoice($purchaseInvoice, $request->user()->id);

            return response()->json([
                'message' => 'Invoice cancelled successfully.',
                'data' => new PurchaseInvoiceResource($purchaseInvoice->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

