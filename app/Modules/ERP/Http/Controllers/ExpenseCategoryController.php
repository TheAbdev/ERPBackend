<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        $categories = ExpenseCategory::where('tenant_id', $request->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate();

        return \App\Modules\ERP\Http\Resources\ExpenseCategoryResource::collection($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = $request->input('is_active', true);

        $category = ExpenseCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category created successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseCategoryResource($category),
        ], 201);
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('view', $expenseCategory);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseCategoryResource($expenseCategory),
        ]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('update', $expenseCategory);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $expenseCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Expense category updated successfully.',
            'data' => new \App\Modules\ERP\Http\Resources\ExpenseCategoryResource($expenseCategory->fresh()),
        ]);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('delete', $expenseCategory);

        $expenseCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense category deleted successfully.',
        ]);
    }
}

