<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Models\Note;
use App\Modules\CRM\Models\NoteAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NoteAttachmentController extends Controller
{
    /**
     * Get all attachments for a note.
     *
     * @param  \App\Modules\CRM\Models\Note  $note
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        $attachments = $note->attachments()->with('uploader')->get();

        return response()->json([
            'success' => true,
            'data' => \App\Modules\CRM\Http\Resources\NoteAttachmentResource::collection($attachments),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'note_id' => 'required|exists:notes,id',
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $note = Note::findOrFail($request->input('note_id'));
        $this->authorize('update', $note);

        $file = $request->file('file');
        $fileName = uniqid().'_'.$file->getClientOriginalName();
        $filePath = $file->storeAs('note-attachments', $fileName, 'local');

        $attachment = NoteAttachment::create([
            'tenant_id' => $request->user()->tenant_id,
            'note_id' => $note->id,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_path' => $filePath,
            'disk' => 'local',
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File attached successfully.',
            'data' => new \App\Modules\CRM\Http\Resources\NoteAttachmentResource($attachment),
        ], 201);
    }

    public function show(NoteAttachment $noteAttachment): JsonResponse
    {
        $this->authorize('view', $noteAttachment->note);

        return response()->json([
            'success' => true,
            'data' => new \App\Modules\CRM\Http\Resources\NoteAttachmentResource($noteAttachment->load('uploader')),
        ]);
    }

    public function destroy(NoteAttachment $noteAttachment): JsonResponse
    {
        $this->authorize('update', $noteAttachment->note);

        Storage::disk($noteAttachment->disk)->delete($noteAttachment->file_path);
        $noteAttachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully.',
        ]);
    }
}

