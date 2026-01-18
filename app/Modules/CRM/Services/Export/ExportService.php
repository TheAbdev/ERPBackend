<?php

namespace App\Modules\CRM\Services\Export;

use App\Core\Services\TenantContext;
use App\Modules\CRM\Models\ExportLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ExportService
{
    protected TenantContext $tenantContext;
    protected LeadExportService $leadExportService;
    protected DealExportService $dealExportService;
    protected ActivityExportService $activityExportService;

    public function __construct(
        TenantContext $tenantContext,
        LeadExportService $leadExportService,
        DealExportService $dealExportService,
        ActivityExportService $activityExportService
    ) {
        $this->tenantContext = $tenantContext;
        $this->leadExportService = $leadExportService;
        $this->dealExportService = $dealExportService;
        $this->activityExportService = $activityExportService;
    }

    /**
     * Export data and generate signed URL.
     *
     * @param  string  $exportType
     * @param  array  $filters
     * @param  int  $userId
     * @param  string  $format
     * @return \App\Modules\CRM\Models\ExportLog
     */
    public function export(string $exportType, array $filters, int $userId, string $format = 'csv'): ExportLog
    {
        $tenantId = $this->tenantContext->getTenantId();

        // Get data based on type
        $data = match ($exportType) {
            'leads' => $this->leadExportService->export($filters),
            'deals' => $this->dealExportService->export($filters),
            'activities' => $this->activityExportService->export($filters),
            default => throw new \InvalidArgumentException("Unknown export type: {$exportType}"),
        };

        // Generate file
        $fileName = $this->generateFileName($exportType, $format);
        $filePath = $this->generateFile($data, $fileName, $format);

        // Create export log
        $exportLog = ExportLog::create([
            'tenant_id' => $tenantId,
            'export_type' => $exportType,
            'filters' => $filters,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'record_count' => count($data),
            'created_by' => $userId,
        ]);

        // Generate signed URL (valid for 1 hour)
        $signedUrl = URL::temporarySignedRoute(
            'api.crm.exports.download',
            now()->addHour(),
            ['exportLog' => $exportLog->id]
        );

        $exportLog->update([
            'signed_url' => $signedUrl,
            'expires_at' => now()->addHour(),
        ]);

        return $exportLog;
    }

    /**
     * Generate file name.
     *
     * @param  string  $exportType
     * @param  string  $format
     * @return string
     */
    protected function generateFileName(string $exportType, string $format): string
    {
        $timestamp = now()->format('Y-m-d_His');
        return "{$exportType}_export_{$timestamp}.{$format}";
    }

    /**
     * Generate file from data.
     *
     * @param  array  $data
     * @param  string  $fileName
     * @param  string  $format
     * @return string
     */
    protected function generateFile(array $data, string $fileName, string $format): string
    {
        $filePath = "exports/{$fileName}";

        if ($format === 'csv') {
            $this->generateCsv($data, $filePath);
        } else {
            throw new \InvalidArgumentException("Unsupported format: {$format}");
        }

        return $filePath;
    }

    /**
     * Generate CSV file.
     *
     * @param  array  $data
     * @param  string  $filePath
     * @return void
     */
    protected function generateCsv(array $data, string $filePath): void
    {
        if (empty($data)) {
            Storage::put($filePath, '');
            return;
        }

        $handle = fopen(Storage::path($filePath), 'w');

        // Write headers
        $headers = array_keys($data[0]);
        fputcsv($handle, $headers);

        // Write data rows
        foreach ($data as $row) {
            fputcsv($handle, array_values($row));
        }

        fclose($handle);
    }
}

