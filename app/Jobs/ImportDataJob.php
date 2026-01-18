<?php

namespace App\Jobs;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\ImportResult;
use App\Modules\CRM\Services\Import\ContactImportService;
use App\Modules\CRM\Services\Import\DealImportService;
use App\Modules\CRM\Services\Import\LeadImportService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportDataJob extends BaseJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importResultId
    ) {
        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function handle(
        TenantContext $tenantContext,
        LeadImportService $leadImportService,
        ContactImportService $contactImportService,
        DealImportService $dealImportService
    ): void {
        $importResult = ImportResult::find($this->importResultId);

        if (! $importResult) {
            Log::error('Import job: Import result not found', ['import_result_id' => $this->importResultId]);
            return;
        }

        // Set tenant context
        $tenantContext->setTenant($importResult->tenant);

        try {
            // Read file
            $filePath = Storage::path($importResult->file_path);
            $rows = $this->readFile($filePath);

            if (empty($rows)) {
                $importResult->update([
                    'status' => 'failed',
                    'error_log' => [['error' => 'No data found in file']],
                    'completed_at' => now(),
                ]);
                return;
            }

            // Import based on type
            $result = match ($importResult->import_type) {
                'leads' => $leadImportService->import($rows, $this->importResultId, $importResult->created_by),
                'contacts' => $contactImportService->import($rows, $this->importResultId, $importResult->created_by),
                'deals' => $dealImportService->import($rows, $this->importResultId, $importResult->created_by),
                default => throw new \Exception("Unknown import type: {$importResult->import_type}"),
            };

            Log::info('Import completed', [
                'import_result_id' => $this->importResultId,
                'success_count' => $result['success_count'],
                'failed_count' => $result['failed_count'],
            ]);
        } catch (\Exception $e) {
            $importResult->update([
                'status' => 'failed',
                'error_log' => [['error' => $e->getMessage()]],
                'completed_at' => now(),
            ]);

            Log::error('Import job failed', [
                'import_result_id' => $this->importResultId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Read file and return rows as array.
     *
     * @param  string  $filePath
     * @return array
     */
    protected function readFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => $this->readCsv($filePath),
            'xlsx', 'xls' => $this->readExcel($filePath),
            default => throw new \Exception("Unsupported file type: {$extension}"),
        };
    }

    /**
     * Read CSV file.
     *
     * @param  string  $filePath
     * @return array
     */
    protected function readCsv(string $filePath): array
    {
        $rows = [];
        $headers = null;

        if (($handle = fopen($filePath, 'r')) !== false) {
            $lineNumber = 0;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;

                if ($lineNumber === 1) {
                    // First row is headers
                    $headers = array_map('trim', $data);
                    continue;
                }

                if (count($data) !== count($headers)) {
                    continue; // Skip malformed rows
                }

                $row = array_combine($headers, array_map('trim', $data));
                $rows[] = $row;
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Read Excel file.
     * Note: This is a basic implementation. For full Excel support, install maatwebsite/excel package.
     *
     * @param  string  $filePath
     * @return array
     */
    protected function readExcel(string $filePath): array
    {
        // For now, throw exception suggesting package installation
        // In production, you would use maatwebsite/excel or similar
        throw new \Exception('Excel import requires maatwebsite/excel package. Please install it via: composer require maatwebsite/excel');
    }
}

