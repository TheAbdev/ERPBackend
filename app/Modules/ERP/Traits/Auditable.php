<?php

namespace App\Modules\ERP\Traits;

use App\Core\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Trait for ERP models that need enhanced audit logging.
 * Extends the base ModelChangeTracker with ERP-specific actions.
 */
trait Auditable
{
    /**
     * Log a custom action for this model.
     *
     * @param  string  $action
     * @param  array|null  $oldValues
     * @param  array|null  $newValues
     * @param  array|null  $metadata
     * @return void
     */
    public function logAuditAction(
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): void {
        if (!isset($this->tenant_id)) {
            return;
        }

        try {
            $auditService = app(AuditService::class);
            $auditService->log($action, $this, $oldValues, $newValues, $metadata);
        } catch (\Exception $e) {
            // Don't break the application if audit logging fails
            Log::error('Failed to log audit action', [
                'model' => get_class($this),
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get fields to exclude from audit logging.
     *
     * @return array
     */
    public function getAuditExclusions(): array
    {
        return [
            'updated_at',
            'created_at',
            'deleted_at',
        ];
    }

    /**
     * Get human-readable action description.
     *
     * @param  string  $action
     * @return string
     */
    public function getAuditActionDescription(string $action): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();

        return match ($action) {
            'create' => "Created {$modelName}: {$identifier}",
            'update' => "Updated {$modelName}: {$identifier}",
            'delete' => "Deleted {$modelName}: {$identifier}",
            'post' => "Posted {$modelName}: {$identifier}",
            'cancel' => "Cancelled {$modelName}: {$identifier}",
            'approve' => "Approved {$modelName}: {$identifier}",
            'activate' => "Activated {$modelName}: {$identifier}",
            'dispose' => "Disposed {$modelName}: {$identifier}",
            'issue' => "Issued {$modelName}: {$identifier}",
            'apply' => "Applied payment to {$modelName}: {$identifier}",
            'reverse' => "Reversed payment for {$modelName}: {$identifier}",
            default => "{$action} {$modelName}: {$identifier}",
        };
    }

    /**
     * Get identifier for audit log (document number, code, etc.).
     *
     * @return string
     */
    protected function getAuditIdentifier(): string
    {
        // Try common identifier fields
        $identifierFields = ['number', 'code', 'invoice_number', 'payment_number', 'entry_number', 'order_number', 'asset_code', 'name'];

        foreach ($identifierFields as $field) {
            if (isset($this->$field)) {
                return (string) $this->$field;
            }
        }

        return "#{$this->id}";
    }
}

