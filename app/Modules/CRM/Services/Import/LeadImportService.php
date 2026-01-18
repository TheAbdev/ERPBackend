<?php

namespace App\Modules\CRM\Services\Import;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\ImportResult;
use App\Modules\CRM\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadImportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Import leads from file data.
     *
     * @param  array  $rows
     * @param  int  $importResultId
     * @param  int  $userId
     * @return array
     */
    public function import(array $rows, int $importResultId, int $userId): array
    {
        $importResult = ImportResult::find($importResultId);
        if (! $importResult) {
            throw new \Exception('Import result not found');
        }

        $importResult->update([
            'status' => 'processing',
            'started_at' => now(),
            'total_rows' => count($rows),
        ]);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            try {
                $this->validateRow($row, $rowIndex + 1);

                // Check for duplicates (by email if provided)
                if (isset($row['email']) && ! empty($row['email'])) {
                    $existing = Lead::where('tenant_id', $this->tenantContext->getTenantId())
                        ->where('email', $row['email'])
                        ->exists();

                    if ($existing) {
                        throw new \Exception("Duplicate email: {$row['email']}");
                    }
                }

                Lead::create([
                    'tenant_id' => $this->tenantContext->getTenantId(),
                    'name' => $row['name'] ?? '',
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'source' => $row['source'] ?? null,
                    'status' => $row['status'] ?? 'new',
                    'assigned_to' => $row['assigned_to'] ?? null,
                    'created_by' => $userId,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ];
            }
        }

        $importResult->update([
            'status' => 'completed',
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'error_log' => $errors,
            'completed_at' => now(),
        ]);

        return [
            'success' => true,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Validate a single row.
     *
     * @param  array  $row
     * @param  int  $rowNumber
     * @return void
     * @throws \Exception
     */
    protected function validateRow(array $row, int $rowNumber): void
    {
        // Required fields
        if (empty($row['name'])) {
            throw new \Exception("Row {$rowNumber}: Name is required");
        }

        // Email validation
        if (isset($row['email']) && ! empty($row['email']) && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Row {$rowNumber}: Invalid email format");
        }

        // Validate assigned_to if provided
        if (isset($row['assigned_to']) && ! empty($row['assigned_to'])) {
            $user = \App\Models\User::where('id', $row['assigned_to'])
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->exists();

            if (! $user) {
                throw new \Exception("Row {$rowNumber}: Invalid assigned_to user ID");
            }
        }
    }
}

