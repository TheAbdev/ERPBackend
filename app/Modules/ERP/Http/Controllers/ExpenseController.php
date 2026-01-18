<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with(['category', 'currency', 'creator', 'approver'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('category_id')) {
            $query->where('expense_category_id', $request->input('category_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
        }

        $expenses = $query->latest('expense_date')->paginate();

        return \App\Modules\ERP\Http\Resources\ExpenseResource::collection($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Expense::class);

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'account_id' => 'nullable|exists:accounts,id',
            'vendor_id' => 'nullable|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'payee_name' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'required|string|max:255',
            'receipt_path' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['expense_number'] = $this->generateExpenseNumber($request->user()->tenant_id);
        $validated['status'] = 'pending';
        $validated['created_by'] = $request->user()->id;

        $expense = Expense::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseResource($expense->load(['category', 'currency'])),
        ], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        $expense->load(['category', 'account', 'vendor', 'currency', 'creator', 'approver']);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseResource($expense),
        ]);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'expense_category_id' => 'sometimes|required|exists:expense_categories,id',
            'account_id' => 'nullable|exists:accounts,id',
            'vendor_id' => 'nullable|exists:accounts,id',
            'currency_id' => 'sometimes|required|exists:currencies,id',
            'payee_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'expense_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|string|max:255',
            'receipt_path' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseResource($expense->fresh()->load(['category', 'currency'])),
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be deleted.',
            ], 422);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }

    public function approve(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be approved.',
            ], 422);
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense approved successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseResource($expense->fresh()->load('approver')),
        ]);
    }

    public function reject(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($expense->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending expenses can be rejected.',
            ], 422);
        }

        $expense->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense rejected successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseResource($expense->fresh()),
        ]);
    }

    protected function generateExpenseNumber(int $tenantId): string
    {
        $prefix = 'EXP-';
        $year = now()->year;
        $lastExpense = Expense::where('tenant_id', $tenantId)
            ->where('expense_number', 'like', "{$prefix}{$year}%")
            ->orderBy('expense_number', 'desc')
            ->first();

        if ($lastExpense) {
            $lastNumber = (int) substr($lastExpense->expense_number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}

