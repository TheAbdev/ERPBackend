<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\StoreNoteRequest;
use App\Modules\CRM\Http\Requests\UpdateNoteRequest;
use App\Modules\CRM\Http\Resources\NoteResource;
use App\Modules\CRM\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Note::class);

        $query = Note::with(['creator', 'mentions', 'noteable', 'replies.creator']);

        // Filter by noteable entity
        if ($request->has('noteable_type') && $request->has('noteable_id')) {
            $query->where('noteable_type', $request->noteable_type)
                ->where('noteable_id', $request->noteable_id);
        }

        // Only show top-level notes (not replies) unless filtering by parent
        if (!$request->has('parent_id')) {
            $query->whereNull('parent_id');
        }

        $notes = $query->latest()->paginate();

        return NoteResource::collection($notes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StoreNoteRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreNoteRequest $request): JsonResponse
    {
        $this->authorize('create', Note::class);

        $note = Note::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        // Parse and store mentions
        $this->parseMentions($note, $request->body);

        $note->load(['creator', 'mentions', 'noteable']);

        return response()->json([
            'data' => new NoteResource($note),
            'message' => 'Note created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Note  $note
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $note->load(['creator', 'mentions', 'noteable']);

        return response()->json([
            'data' => new NoteResource($note),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdateNoteRequest  $request
     * @param  \App\Modules\CRM\Models\Note  $note
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        // Re-parse mentions if body was updated
        if ($request->has('body')) {
            $note->mentions()->delete();
            $this->parseMentions($note, $request->body);
        }

        $note->load(['creator', 'mentions', 'noteable']);

        return response()->json([
            'data' => new NoteResource($note),
            'message' => 'Note updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Note  $note
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Note $note): JsonResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully.',
        ]);
    }

    /**
     * Store a reply to a note.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modules\CRM\Models\Note  $note
     * @return \Illuminate\Http\JsonResponse
     */
    public function reply(\Illuminate\Http\Request $request, Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $reply = Note::create([
            'tenant_id' => $note->tenant_id,
            'parent_id' => $note->id,
            'noteable_type' => $note->noteable_type,
            'noteable_id' => $note->noteable_id,
            'body' => $validated['body'],
            'created_by' => $request->user()->id,
        ]);

        // Parse and store mentions
        $this->parseMentions($reply, $validated['body']);

        $reply->load(['creator', 'mentions', 'parent']);

        return response()->json([
            'data' => new NoteResource($reply),
            'message' => 'Reply created successfully.',
        ], 201);
    }

    /**
     * Get all replies for a note.
     *
     * @param  \App\Modules\CRM\Models\Note  $note
     * @return \Illuminate\Http\JsonResponse
     */
    public function replies(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $replies = $note->replies()->with(['creator', 'mentions'])->get();

        return response()->json([
            'data' => NoteResource::collection($replies),
        ]);
    }

    /**
     * Parse mentions from note body and store them.
     *
     * @param  \App\Modules\CRM\Models\Note  $note
     * @param  string  $body
     * @return void
     */
    protected function parseMentions(Note $note, string $body): void
    {
        // Pattern: @user_id or @{user_id}
        preg_match_all('/@\{?(\d+)\}?/', $body, $matches);

        if (! empty($matches[1])) {
            $userIds = array_unique($matches[1]);
            $tenantId = $note->tenant_id;
            $mentionedBy = auth()->user();

            foreach ($userIds as $userId) {
                // Verify user exists and belongs to same tenant
                $user = \App\Models\User::where('id', $userId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($user && ! $note->mentions()->where('users.id', $userId)->exists()) {
                    $note->mentions()->attach($userId, ['tenant_id' => $tenantId]);

                    // Dispatch event for notification
                    event(new \App\Events\NoteMentioned($note, $user, $mentionedBy));
                }
            }
        }
    }
}

