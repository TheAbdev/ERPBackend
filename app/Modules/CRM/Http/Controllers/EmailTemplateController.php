<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EmailTemplate::class);
        $templates = EmailTemplate::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate();
        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EmailTemplate::class);
        $validated = $request->validate([
            'name' => 'required|string',
            'subject' => 'required|string',
            'body' => 'required|string',
            'type' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $validated['tenant_id'] = $request->user()->tenant_id;
        $template = EmailTemplate::create($validated);
        return response()->json([
            'success' => true,
            'message' => 'Email template created successfully.',
            'data' => $template,
        ], 201);
    }

    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('view', $emailTemplate);
        return response()->json([
            'success' => true,
            'data' => $emailTemplate,
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('update', $emailTemplate);
        $emailTemplate->update($request->validate([
            'name' => 'sometimes|string',
            'subject' => 'sometimes|string',
            'body' => 'sometimes|string',
            'type' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]));
        return response()->json([
            'success' => true,
            'message' => 'Email template updated successfully.',
            'data' => $emailTemplate->fresh(),
        ]);
    }

    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('delete', $emailTemplate);
        $emailTemplate->delete();
        return response()->json([
            'success' => true,
            'message' => 'Email template deleted successfully.',
        ]);
    }
}


