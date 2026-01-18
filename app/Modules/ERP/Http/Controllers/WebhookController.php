<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERP\Http\Resources\WebhookResource;
use App\Modules\ERP\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    /**
     * Display a listing of webhooks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Webhook::class);

        $query = Webhook::where('tenant_id', $request->user()->tenant_id)
            ->with('deliveries');

        if ($request->has('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $webhooks = $query->orderBy('created_at', 'desc')->paginate();

        return WebhookResource::collection($webhooks);
    }

    /**
     * Store a newly created webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Webhook::class);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'module' => ['required', 'string', Rule::in(['ERP', 'CRM', 'Core'])],
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $webhook = Webhook::create([
            'tenant_id' => $request->user()->tenant_id,
            'url' => $validated['url'],
            'secret' => $validated['secret'] ?? null,
            'module' => $validated['module'],
            'event_types' => $validated['event_types'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Webhook created successfully.',
            'data' => new WebhookResource($webhook),
        ], 201);
    }

    /**
     * Display the specified webhook.
     *
     * @param  \App\Modules\ERP\Models\Webhook  $webhook
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Webhook $webhook): JsonResponse
    {
        $this->authorize('view', $webhook);

        $webhook->load('deliveries');

        return response()->json([
            'data' => new WebhookResource($webhook),
        ]);
    }

    /**
     * Update the specified webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\ERP\Models\Webhook  $webhook
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $validated = $request->validate([
            'url' => ['sometimes', 'required', 'url', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
            'module' => ['sometimes', 'required', 'string', Rule::in(['ERP', 'CRM', 'Core'])],
            'event_types' => ['sometimes', 'required', 'array', 'min:1'],
            'event_types.*' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $webhook->update($validated);

        return response()->json([
            'message' => 'Webhook updated successfully.',
            'data' => new WebhookResource($webhook->fresh()),
        ]);
    }

    /**
     * Remove the specified webhook.
     *
     * @param  \App\Modules\ERP\Models\Webhook  $webhook
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook deleted successfully.',
        ]);
    }
}

