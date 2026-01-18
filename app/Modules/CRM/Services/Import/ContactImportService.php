<?php

namespace App\Modules\CRM\Services\Import;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Contact;
use App\Modules\CRM\Models\ImportResult;
use Illuminate\Support\Facades\Log;

class ContactImportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Import contacts from file data.
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
                    $existing = Contact::where('tenant_id', $this->tenantContext->getTenantId())
                        ->where('email', $row['email'])
                        ->exists();

                    if ($existing) {
                        throw new \Exception("Duplicate email: {$row['email']}");
                    }
                }

                Contact::create([
                    'tenant_id' => $this->tenantContext->getTenantId(),
                    'lead_id' => $row['lead_id'] ?? null,
                    'first_name' => $row['first_name'] ?? '',
                    'last_name' => $row['last_name'] ?? '',
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'job_title' => $row['job_title'] ?? null,
                    'notes' => $row['notes'] ?? null,
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
        if (empty($row['first_name'])) {
            throw new \Exception("Row {$rowNumber}: First name is required");
        }

        if (empty($row['last_name'])) {
            throw new \Exception("Row {$rowNumber}: Last name is required");
        }

        // Email validation
        if (isset($row['email']) && ! empty($row['email']) && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Row {$rowNumber}: Invalid email format");
        }

        // Validate lead_id if provided
        if (isset($row['lead_id']) && ! empty($row['lead_id'])) {
            $lead = \App\Modules\CRM\Models\Lead::where('id', $row['lead_id'])
                ->where('tenant_id', $this->tenantContext->getTenantId())
                ->exists();

            if (! $lead) {
                throw new \Exception("Row {$rowNumber}: Invalid lead_id");
            }
        }
    }
}

