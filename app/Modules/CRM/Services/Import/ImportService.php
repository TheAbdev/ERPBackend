<?php

namespace App\Modules\CRM\Services\Import;

use App\Core\Services\TenantContext;
use App\Jobs\ImportDataJob;
use App\Modules\CRM\Models\ImportResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Process import file and dispatch job.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $importType
     * @param  int  $userId
     * @return \App\Modules\CRM\Models\ImportResult
     */
    public function processImport(UploadedFile $file, string $importType, int $userId): ImportResult
    {
        $tenantId = $this->tenantContext->getTenantId();

        // Validate file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['csv', 'xlsx', 'xls'])) {
            throw new \InvalidArgumentException('Invalid file type. Only CSV and Excel files are supported.');
        }

        // Store file
        $fileName = 'imports/'.uniqid().'_'.$file->getClientOriginalName();
        $filePath = $file->storeAs('imports', basename($fileName), 'local');

        // Create import result record
        $importResult = ImportResult::create([
            'tenant_id' => $tenantId,
            'import_type' => $importType,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending',
            'created_by' => $userId,
        ]);

        // Dispatch import job
        ImportDataJob::dispatch($importResult->id);

        return $importResult;
    }

    /**
     * Get import result by ID.
     *
     * @param  int  $id
     * @return \App\Modules\CRM\Models\ImportResult|null
     */
    public function getImportResult(int $id): ?ImportResult
    {
        return ImportResult::where('tenant_id', $this->tenantContext->getTenantId())
            ->find($id);
    }
}

