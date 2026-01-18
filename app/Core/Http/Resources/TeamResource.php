<?php

namespace App\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'team_lead_id' => $this->team_lead_id,
            'team_lead' => $this->whenLoaded('teamLead', fn () => [
                'id' => $this->teamLead->id,
                'name' => $this->teamLead->name,
                'email' => $this->teamLead->email,
            ]),
            'color' => $this->color,
            'is_active' => $this->is_active,
            'users' => $this->whenLoaded('users', fn () => $this->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role ?? 'member',
                ];
            })),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

