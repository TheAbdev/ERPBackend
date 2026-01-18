<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\Tag;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Tag::class);

        $query = Tag::where('tenant_id', $request->user()->tenant_id);

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $tags = $query->orderBy('name')->paginate();

        return \App\Core\Http\Resources\TagResource::collection($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Tag::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'type' => 'nullable|string|max:255',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;

        // Check if tag with same name already exists
        $existing = Tag::where('tenant_id', $request->user()->tenant_id)
            ->where('name', $validated['name'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Tag with this name already exists.',
            ], 422);
        }

        $tag = Tag::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully.',
            'data' => new \App\Core\Http\Resources\TagResource($tag),
        ], 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        $this->authorize('view', $tag);

        return response()->json([
            'success' => true,
            'data' => new \App\Core\Http\Resources\TagResource($tag),
        ]);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'nullable|string|max:7',
            'type' => 'nullable|string|max:255',
        ]);

        // Check if another tag with same name exists
        if (isset($validated['name'])) {
            $existing = Tag::where('tenant_id', $request->user()->tenant_id)
                ->where('name', $validated['name'])
                ->where('id', '!=', $tag->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag with this name already exists.',
                ], 422);
            }
        }

        $tag->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully.',
            'data' => new \App\Core\Http\Resources\TagResource($tag->fresh()),
        ]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully.',
        ]);
    }
}

