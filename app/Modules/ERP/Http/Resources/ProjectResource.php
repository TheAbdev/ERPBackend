<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'budget' => $this->budget,
            'actual_cost' => $this->actual_cost,
            'remaining_budget' => $this->remaining_budget,
            'progress' => round($this->progress, 2),
            'manager' => $this->whenLoaded('manager', fn() => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
                'email' => $this->manager->email,
            ]),
            'manager_id' => $this->manager_id,
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_by' => $this->created_by,
            'tasks_count' => $this->when(isset($this->tasks_count), $this->tasks_count),
            'tasks' => ProjectTaskResource::collection($this->whenLoaded('tasks')),
            'budgets' => ProjectBudgetResource::collection($this->whenLoaded('budgets')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}















