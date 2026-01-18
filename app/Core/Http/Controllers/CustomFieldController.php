<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\CustomField;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomFieldController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CustomField::class);

        $query = CustomField::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $customFields = $query->orderBy('display_order')->orderBy('label')->paginate();

        return \App\Core\Http\Resources\CustomFieldResource::collection($customFields);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CustomField::class);

        $validated = $request->validate([
            'entity_type' => 'required|string|max:255',
            'field_name' => 'required|string|max:255|regex:/^[a-z][a-z0-9_]*$/',
            'label' => 'required|string|max:255',
            'type' => 'required|string|in:text,number,email,date,select,checkbox,textarea',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
            'default_value' => 'nullable|string',
            'validation_rules' => 'nullable|array',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = $request->input('is_active', true);
        $validated['display_order'] = $request->input('display_order', 0);

        $customField = CustomField::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom field created successfully.',
            'data' => new \App\Core\Http\Resources\CustomFieldResource($customField),
        ], 201);
    }

    public function show(CustomField $customField): JsonResponse
    {
        $this->authorize('view', $customField);

        return response()->json([
            'success' => true,
            'data' => new \App\Core\Http\Resources\CustomFieldResource($customField),
        ]);
    }

    public function update(Request $request, CustomField $customField): JsonResponse
    {
        $this->authorize('update', $customField);

        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:text,number,email,date,select,checkbox,textarea',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
            'default_value' => 'nullable|string',
            'validation_rules' => 'nullable|array',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $customField->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom field updated successfully.',
            'data' => new \App\Core\Http\Resources\CustomFieldResource($customField->fresh()),
        ]);
    }

    public function destroy(CustomField $customField): JsonResponse
    {
        $this->authorize('delete', $customField);

        $customField->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom field deleted successfully.',
        ]);
    }
}

