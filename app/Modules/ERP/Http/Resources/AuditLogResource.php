<?php

namespace App\Modules\ERP\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $description = $this->metadata['description'] ?? $this->getDefaultDescription();

        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_label' => $this->getActionLabel(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'model_type' => $this->model_type,
            'model_name' => $this->model_name,
            'model_id' => $this->model_id,
            'model' => $this->whenLoaded('model', fn () => $this->getModelIdentifier()),
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'metadata' => $this->metadata,
            'description' => $description,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'request_id' => $this->request_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Get human-readable action label.
     *
     * @return string
     */
    protected function getActionLabel(): string
    {
        return match ($this->action) {
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'post' => 'Posted',
            'cancel' => 'Cancelled',
            'approve' => 'Approved',
            'activate' => 'Activated',
            'dispose' => 'Disposed',
            'issue' => 'Issued',
            'apply' => 'Applied',
            'reverse' => 'Reversed',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get default description if not in metadata.
     *
     * @return string
     */
    protected function getDefaultDescription(): string
    {
        $actionLabel = $this->getActionLabel();
        $modelName = $this->model_name ?? 'Record';
        $identifier = $this->getModelIdentifier();

        return "{$actionLabel} {$modelName}" . ($identifier ? ": {$identifier}" : '');
    }

    /**
     * Get model identifier for display.
     *
     * @return string|null
     */
    protected function getModelIdentifier(): ?string
    {
        if (!$this->relationLoaded('model') || !$this->model) {
            return null;
        }

        // Try common identifier fields
        $identifierFields = ['number', 'code', 'invoice_number', 'payment_number', 'entry_number', 'order_number', 'asset_code', 'name'];

        foreach ($identifierFields as $field) {
            if (isset($this->model->$field)) {
                return (string) $this->model->$field;
            }
        }

        return "#{$this->model_id}";
    }
}

