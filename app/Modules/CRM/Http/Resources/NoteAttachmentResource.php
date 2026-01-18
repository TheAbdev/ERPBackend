<?php

namespace App\Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note_id' => $this->note_id,
            'file_name' => $this->file_name,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_path' => $this->file_path,
            'disk' => $this->disk,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

