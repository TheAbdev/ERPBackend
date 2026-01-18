<?php

namespace App\Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'job_title' => $this->job_title,
            'notes' => $this->notes,
            'linkedin_url' => $this->linkedin_url,
            'twitter_handle' => $this->twitter_handle,
            'facebook_url' => $this->facebook_url,
            'instagram_handle' => $this->instagram_handle,
            'lead' => $this->whenLoaded('lead', function () {
                return [
                    'id' => $this->lead->id,
                    'name' => $this->lead->name,
                    'email' => $this->lead->email,
                ];
            }),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

