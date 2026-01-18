<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\CreditNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CreditNoteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CreditNote::class);

        $query = CreditNote::with(['salesInvoice', 'currency', 'creator', 'issuer'])
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

        $creditNotes = $query->latest('issue_date')->paginate();

        return \App\Modules\ERP\Http\Resources\CreditNoteResource::collection($creditNotes);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CreditNote::class);

        $validated = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,id',
            'reason' => 'required|string|max:255',
            'reason_description' => 'nullable|string',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $salesInvoice = \App\Modules\ERP\Models\SalesInvoice::findOrFail($validated['sales_invoice_id']);

        $creditNote = DB::transaction(function () use ($validated, $request, $salesInvoice) {
            $creditNote = CreditNote::create([
                'tenant_id' => $request->user()->tenant_id,
                'credit_note_number' => $this->generateCreditNoteNumber($request->user()->tenant_id),
                'sales_invoice_id' => $validated['sales_invoice_id'],
                'fiscal_year_id' => $salesInvoice->fiscal_year_id,
                'fiscal_period_id' => $salesInvoice->fiscal_period_id,
                'currency_id' => $salesInvoice->currency_id,
                'customer_name' => $salesInvoice->customer_name,
                'customer_email' => $salesInvoice->customer_email,
                'customer_address' => $salesInvoice->customer_address,
                'reason' => $validated['reason'],
                'reason_description' => $validated['reason_description'],
                'status' => 'draft',
                'issue_date' => $validated['issue_date'],
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'remaining_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($validated['items'] as $index => $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $itemTax = $lineTotal * ($item['tax_rate'] ?? 0) / 100;
                $subtotal += $lineTotal;
                $taxAmount += $itemTax;

                $creditNote->items()->create([
                    'tenant_id' => $request->user()->tenant_id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $itemTax,
                    'line_total' => $lineTotal + $itemTax,
                    'display_order' => $index + 1,
                ]);
            }

            $creditNote->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
                'remaining_amount' => $subtotal + $taxAmount,
            ]);

            return $creditNote->load('items');
        });

        return response()->json([
            'success' => true,
            'message' => 'Credit note created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\CreditNoteResource($creditNote),
        ], 201);
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        $this->authorize('view', $creditNote);

        $creditNote->load(['salesInvoice', 'currency', 'items.product', 'creator', 'issuer']);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\CreditNoteResource($creditNote),
        ]);
    }

    public function update(Request $request, CreditNote $creditNote): JsonResponse
    {
        $this->authorize('update', $creditNote);

        if ($creditNote->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft credit notes can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'sometimes|required|string|max:255',
            'reason_description' => 'nullable|string',
            'issue_date' => 'sometimes|required|date',
            'items' => 'sometimes|required|array|min:1',
            'notes' => 'nullable|string',
        ]);

        $creditNote->update($validated);

        if (isset($validated['items'])) {
            $creditNote->items()->delete();
            $subtotal = 0;
            $taxAmount = 0;

            foreach ($validated['items'] as $index => $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $itemTax = $lineTotal * ($item['tax_rate'] ?? 0) / 100;
                $subtotal += $lineTotal;
                $taxAmount += $itemTax;

                $creditNote->items()->create([
                    'tenant_id' => $request->user()->tenant_id,
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $itemTax,
                    'line_total' => $lineTotal + $itemTax,
                    'display_order' => $index + 1,
                ]);
            }

            $creditNote->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
                'remaining_amount' => $subtotal + $taxAmount,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Credit note updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\CreditNoteResource($creditNote->fresh()->load('items')),
        ]);
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        $this->authorize('delete', $creditNote);

        if ($creditNote->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft credit notes can be deleted.',
            ], 422);
        }

        $creditNote->delete();

        return response()->json([
            'success' => true,
            'message' => 'Credit note deleted successfully.',
        ]);
    }

    public function issue(Request $request, CreditNote $creditNote): JsonResponse
    {
        $this->authorize('update', $creditNote);

        if ($creditNote->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Credit note is already issued.',
            ], 422);
        }

        $creditNote->update([
            'status' => 'issued',
            'issued_by' => $request->user()->id,
            'issued_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Credit note issued successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\CreditNoteResource($creditNote->fresh()),
        ]);
    }

    protected function generateCreditNoteNumber(int $tenantId): string
    {
        $prefix = 'CN-';
        $year = now()->year;
        $lastCreditNote = CreditNote::where('tenant_id', $tenantId)
            ->where('credit_note_number', 'like', "{$prefix}{$year}%")
            ->orderBy('credit_note_number', 'desc')
            ->first();

        if ($lastCreditNote) {
            $lastNumber = (int) substr($lastCreditNote->credit_note_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}

