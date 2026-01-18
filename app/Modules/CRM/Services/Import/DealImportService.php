<?php

namespace App\Modules\CRM\Services\Import;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\ImportResult;
use Illuminate\Support\Facades\Log;

class DealImportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Import deals from file data.
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

                Deal::create([
                    'tenant_id' => $this->tenantContext->getTenantId(),
                    'pipeline_id' => $row['pipeline_id'],
                    'stage_id' => $row['stage_id'],
                    'lead_id' => $row['lead_id'] ?? null,
                    'contact_id' => $row['contact_id'] ?? null,
                    'account_id' => $row['account_id'] ?? null,
                    'title' => $row['title'],
                    'amount' => $row['amount'] ?? 0,
                    'currency' => $row['currency'] ?? 'USD',
                    'probability' => $row['probability'] ?? 0,
                    'expected_close_date' => isset($row['expected_close_date']) ? $row['expected_close_date'] : null,
                    'status' => $row['status'] ?? 'open',
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
        if (empty($row['title'])) {
            throw new \Exception("Row {$rowNumber}: Title is required");
        }

        if (empty($row['pipeline_id'])) {
            throw new \Exception("Row {$rowNumber}: Pipeline ID is required");
        }

        if (empty($row['stage_id'])) {
            throw new \Exception("Row {$rowNumber}: Stage ID is required");
        }

        // Validate pipeline_id
        $pipeline = \App\Modules\CRM\Models\Pipeline::where('id', $row['pipeline_id'])
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->first();

        if (! $pipeline) {
            throw new \Exception("Row {$rowNumber}: Invalid pipeline_id");
        }

        // Validate stage_id belongs to pipeline
        $stage = \App\Modules\CRM\Models\PipelineStage::where('id', $row['stage_id'])
            ->where('pipeline_id', $row['pipeline_id'])
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->exists();

        if (! $stage) {
            throw new \Exception("Row {$rowNumber}: Invalid stage_id for pipeline");
        }

        // Validate amount
        if (isset($row['amount']) && ! is_numeric($row['amount'])) {
            throw new \Exception("Row {$rowNumber}: Amount must be numeric");
        }

        // Validate probability
        if (isset($row['probability']) && ($row['probability'] < 0 || $row['probability'] > 100)) {
            throw new \Exception("Row {$rowNumber}: Probability must be between 0 and 100");
        }

        // Validate date
        if (isset($row['expected_close_date']) && ! empty($row['expected_close_date'])) {
            if (! strtotime($row['expected_close_date'])) {
                throw new \Exception("Row {$rowNumber}: Invalid expected_close_date format");
            }
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





