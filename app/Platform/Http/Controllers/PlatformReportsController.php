<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class PlatformReportsController extends Controller
{
    /**
     * Get tenants summary report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tenantsSummary(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : Carbon::now();

        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $newTenantsThisMonth = Tenant::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        $tenantsByStatus = Tenant::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'data' => [
                'total_tenants' => $totalTenants,
                'active_tenants' => $activeTenants,
                'suspended_tenants' => $suspendedTenants,
                'new_tenants_this_month' => $newTenantsThisMonth,
                'tenants_by_status' => $tenantsByStatus,
            ],
        ]);
    }

    /**
     * Get users summary report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersSummary(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : Carbon::now();

        $totalUsers = User::count();
        // Users table doesn't have status column, so we use total count
        $activeUsers = User::count();

        $usersByTenant = Tenant::withCount('users')->get()->map(function ($tenant) {
            return [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'users_count' => $tenant->users_count ?? 0,
            ];
        });

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'users_by_tenant' => $usersByTenant,
            ],
        ]);
    }

    /**
     * Get usage report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function usageReport(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : Carbon::now();
        $tenantId = $request->input('tenant_id');

        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();

        $topTenantsByUsage = $tenants->map(function ($tenant) {
            return [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'api_calls' => 0, // Placeholder
                'storage_used' => 0, // Placeholder
            ];
        })->sortByDesc('api_calls')->take(10)->values();

        return response()->json([
            'data' => [
                'period' => $dateFrom->format('Y-m-d') . ' to ' . $dateTo->format('Y-m-d'),
                'total_api_calls' => 0, // Placeholder
                'total_storage_used' => 0, // Placeholder
                'top_tenants_by_usage' => $topTenantsByUsage,
            ],
        ]);
    }

    /**
     * Get activity report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityReport(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : Carbon::now();

        // Placeholder - implement actual activity tracking
        $totalActivities = 0;
        $activitiesByType = [];
        $mostActiveUsers = [];

        return response()->json([
            'data' => [
                'period' => $dateFrom->format('Y-m-d') . ' to ' . $dateTo->format('Y-m-d'),
                'total_activities' => $totalActivities,
                'activities_by_type' => $activitiesByType,
                'most_active_users' => $mostActiveUsers,
            ],
        ]);
    }

    /**
     * Generate and export report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|in:tenants-summary,users-summary,usage,activity,system-health',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'tenant_id' => 'sometimes|integer|exists:tenants,id',
            'format' => 'required|in:csv,pdf,excel',
        ]);

        // Generate report data (placeholder - implement actual report generation)
        $reportData = $this->generateReportData($validated);
        
        // Generate filename with correct extension
        $format = $validated['format'];
        $extension = match($format) {
            'excel' => 'xlsx',
            'csv' => 'csv',
            'pdf' => 'pdf',
            default => $format,
        };
        $reportName = $validated['report_type'] . '_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
        $reportPath = 'reports/' . $reportName;
        
        // Ensure reports directory exists
        Storage::disk('public')->makeDirectory('reports');
        
        // Save file
        $fileContent = $this->formatReportContent($reportData, $format);
        Storage::disk('public')->put($reportPath, $fileContent);
        
        // Return download URL that will be served by the download route
        $downloadUrl = '/api/platform/reports/download/' . basename($reportPath);

        return response()->json([
            'data' => [
                'report_type' => $validated['report_type'],
                'generated_at' => now()->toIso8601String(),
                'format' => $validated['format'],
                'download_url' => $downloadUrl,
            ],
        ]);
    }

    /**
     * Download report file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $filename
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function download(Request $request, string $filename): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        // Security: Only allow downloading files from reports directory
        // Prevent directory traversal attacks
        $filename = basename($filename);
        
        $filePath = 'reports/' . $filename;
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'message' => 'Report file not found',
            ], 404);
        }

        // Get file mime type
        $mimeType = Storage::disk('public')->mimeType($filePath);
        if (!$mimeType) {
            // Default mime types based on extension
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'csv' => 'text/csv',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xls' => 'application/vnd.ms-excel',
                'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
        }

        $fileContent = Storage::disk('public')->get($filePath);
        
        return response($fileContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate report data.
     *
     * @param  array  $params
     * @return array
     */
    protected function generateReportData(array $params): array
    {
        $reportType = $params['report_type'];
        $dateFrom = isset($params['date_from']) ? Carbon::parse($params['date_from']) : Carbon::now()->subMonth();
        $dateTo = isset($params['date_to']) ? Carbon::parse($params['date_to']) : Carbon::now();
        
        $data = [];
        
        switch ($reportType) {
            case 'tenants-summary':
                $data = $this->getTenantsSummaryData($dateFrom, $dateTo);
                break;
            case 'users-summary':
                $data = $this->getUsersSummaryData($dateFrom, $dateTo);
                break;
            case 'usage':
                $data = $this->getUsageReportData($dateFrom, $dateTo, $params['tenant_id'] ?? null);
                break;
            case 'activity':
                $data = $this->getActivityReportData($dateFrom, $dateTo);
                break;
            case 'system-health':
                $data = $this->getSystemHealthReportData();
                break;
        }
        
        return [
            'report_type' => $reportType,
            'generated_at' => now()->toIso8601String(),
            'date_from' => $dateFrom->toIso8601String(),
            'date_to' => $dateTo->toIso8601String(),
            'data' => $data,
        ];
    }

    /**
     * Get tenants summary data.
     *
     * @param  \Carbon\Carbon  $dateFrom
     * @param  \Carbon\Carbon  $dateTo
     * @return array
     */
    protected function getTenantsSummaryData(Carbon $dateFrom, Carbon $dateTo): array
    {
        $tenants = Tenant::all();
        
        return [
            [
                'Metric' => 'Total Tenants',
                'Value' => $tenants->count(),
            ],
            [
                'Metric' => 'Active Tenants',
                'Value' => $tenants->where('status', 'active')->count(),
            ],
            [
                'Metric' => 'Suspended Tenants',
                'Value' => $tenants->where('status', 'suspended')->count(),
            ],
            [
                'Metric' => 'New Tenants (Period)',
                'Value' => Tenant::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            ],
        ];
    }

    /**
     * Get users summary data.
     *
     * @param  \Carbon\Carbon  $dateFrom
     * @param  \Carbon\Carbon  $dateTo
     * @return array
     */
    protected function getUsersSummaryData(Carbon $dateFrom, Carbon $dateTo): array
    {
        $users = User::all();
        $usersByTenant = Tenant::withCount('users')->get();
        
        $data = [
            [
                'Metric' => 'Total Users',
                'Value' => $users->count(),
            ],
        ];
        
        foreach ($usersByTenant as $tenant) {
            $data[] = [
                'Tenant' => $tenant->name,
                'Users Count' => $tenant->users_count ?? 0,
            ];
        }
        
        return $data;
    }

    /**
     * Get usage report data.
     *
     * @param  \Carbon\Carbon  $dateFrom
     * @param  \Carbon\Carbon  $dateTo
     * @param  int|null  $tenantId
     * @return array
     */
    protected function getUsageReportData(Carbon $dateFrom, Carbon $dateTo, ?int $tenantId): array
    {
        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', $tenantId);
        }
        
        $tenants = $query->get();
        
        $data = [];
        foreach ($tenants as $tenant) {
            $data[] = [
                'Tenant Name' => $tenant->name,
                'API Calls' => 0, // Placeholder
                'Storage Used (MB)' => 0, // Placeholder
            ];
        }
        
        return $data;
    }

    /**
     * Get activity report data.
     *
     * @param  \Carbon\Carbon  $dateFrom
     * @param  \Carbon\Carbon  $dateTo
     * @return array
     */
    protected function getActivityReportData(Carbon $dateFrom, Carbon $dateTo): array
    {
        // Placeholder - implement actual activity tracking
        return [
            [
                'Metric' => 'Total Activities',
                'Value' => 0,
            ],
        ];
    }

    /**
     * Get system health report data.
     *
     * @return array
     */
    protected function getSystemHealthReportData(): array
    {
        return [
            [
                'Metric' => 'System Status',
                'Value' => 'Healthy',
            ],
        ];
    }

    /**
     * Format report content.
     *
     * @param  array  $data
     * @param  string  $format
     * @return string
     */
    protected function formatReportContent(array $data, string $format): string
    {
        switch (strtolower($format)) {
            case 'pdf':
                return $this->generatePdf($data);
            case 'csv':
                return $this->generateCsv($data);
            case 'excel':
            case 'xlsx':
                return $this->generateExcel($data);
            default:
                return json_encode($data, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Generate PDF content.
     *
     * @param  array  $data
     * @return string
     */
    protected function generatePdf(array $data): string
    {
        $html = $this->generateHtmlReport($data);
        
        try {
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            $pdfContent = $pdf->output();
            
            // Verify PDF content starts with PDF header
            if (substr($pdfContent, 0, 4) !== '%PDF') {
                \Log::error('Generated PDF content is not valid PDF', [
                    'first_bytes' => substr($pdfContent, 0, 100),
                ]);
                throw new \Exception('Invalid PDF content generated');
            }
            
            return $pdfContent;
        } catch (\Exception $e) {
            \Log::error('PDF generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Try to create a simple PDF manually as fallback
            // For now, return error message in HTML format
            return $this->generateErrorPdf($e->getMessage());
        }
    }

    /**
     * Generate error PDF.
     *
     * @param  string  $errorMessage
     * @return string
     */
    protected function generateErrorPdf(string $errorMessage): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Generation Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #d32f2f; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>Report Generation Error</h1>
    <p>An error occurred while generating the PDF report.</p>
    <p><strong>Error:</strong> ' . htmlspecialchars($errorMessage) . '</p>
    <p>Please contact the administrator.</p>
</body>
</html>';
        
        try {
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            return $pdf->output();
        } catch (\Exception $e) {
            // Last resort: return HTML
            return $html;
        }
    }

    /**
     * Generate CSV content.
     *
     * @param  array  $data
     * @return string
     */
    protected function generateCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Add header
        if (isset($data['report_type'])) {
            fputcsv($output, ['Report Type', $data['report_type']]);
            fputcsv($output, ['Generated At', $data['generated_at'] ?? now()->toIso8601String()]);
            if (isset($data['date_from']) && isset($data['date_to'])) {
                fputcsv($output, ['Date From', $data['date_from']]);
                fputcsv($output, ['Date To', $data['date_to']]);
            }
            fputcsv($output, []); // Empty row
        }
        
        // Add data rows
        if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
            // Get headers from first row
            $firstRow = $data['data'][0];
            if (is_array($firstRow)) {
                $headers = array_keys($firstRow);
                fputcsv($output, $headers);
                
                // Add data rows
                foreach ($data['data'] as $row) {
                    if (is_array($row)) {
                        $values = [];
                        foreach ($headers as $header) {
                            $value = $row[$header] ?? '';
                            // Convert arrays/objects to JSON string
                            if (is_array($value) || is_object($value)) {
                                $value = json_encode($value);
                            }
                            $values[] = $value;
                        }
                        fputcsv($output, $values);
                    }
                }
            } else {
                // Simple array of values
                foreach ($data['data'] as $row) {
                    fputcsv($output, is_array($row) ? array_values($row) : [$row]);
                }
            }
        }
        
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);
        
        return $csvString;
    }

    /**
     * Generate Excel content using Maatwebsite\Excel.
     *
     * @param  array  $data
     * @return string
     */
    protected function generateExcel(array $data): string
    {
        try {
            // Create a temporary file path
            $tempPath = storage_path('app/temp/' . uniqid('excel_', true) . '.xlsx');
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Prepare data for Excel
            $excelData = [];
            
            // Add header row
            if (isset($data['report_type'])) {
                $excelData[] = ['Report Type', $data['report_type']];
                $excelData[] = ['Generated At', $data['generated_at'] ?? now()->toIso8601String()];
                if (isset($data['date_from']) && isset($data['date_to'])) {
                    $excelData[] = ['Date From', $data['date_from']];
                    $excelData[] = ['Date To', $data['date_to']];
                }
                $excelData[] = []; // Empty row
            }
            
            // Add data rows
            if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
                $firstRow = $data['data'][0];
                if (is_array($firstRow)) {
                    // Add headers
                    $headers = array_keys($firstRow);
                    $excelData[] = $headers;
                    
                    // Add data rows
                    foreach ($data['data'] as $row) {
                        if (is_array($row)) {
                            $values = [];
                            foreach ($headers as $header) {
                                $value = $row[$header] ?? '';
                                // Convert arrays/objects to JSON string
                                if (is_array($value) || is_object($value)) {
                                    $value = json_encode($value);
                                }
                                $values[] = $value;
                            }
                            $excelData[] = $values;
                        }
                    }
                } else {
                    // Simple array of values
                    foreach ($data['data'] as $row) {
                        $excelData[] = is_array($row) ? array_values($row) : [$row];
                    }
                }
            }
            
            // Create Excel file using PhpSpreadsheet directly (simpler than creating export class)
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set data
            $rowIndex = 1;
            foreach ($excelData as $row) {
                $colIndex = 1;
                foreach ($row as $cellValue) {
                    $sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $cellValue);
                    $colIndex++;
                }
                $rowIndex++;
            }
            
            // Auto-size columns
            foreach (range(1, $colIndex - 1) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
            
            // Write to file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempPath);
            
            // Read file content
            $excelContent = file_get_contents($tempPath);
            
            // Clean up temp file
            @unlink($tempPath);
            
            return $excelContent;
        } catch (\Exception $e) {
            \Log::error('Excel generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Fallback to CSV
            return $this->generateCsv($data);
        }
    }

    /**
     * Generate HTML report for PDF.
     *
     * @param  array  $data
     * @return string
     */
    protected function generateHtmlReport(array $data): string
    {
        $reportType = $data['report_type'] ?? 'Report';
        $generatedAt = $data['generated_at'] ?? now()->toIso8601String();
        $reportData = $data['data'] ?? [];
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($reportType) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .meta { margin-bottom: 20px; color: #666; }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars(ucwords(str_replace('-', ' ', $reportType))) . ' Report</h1>
    <div class="meta">
        <p><strong>Generated At:</strong> ' . htmlspecialchars($generatedAt) . '</p>
    </div>';
        
        if (!empty($reportData)) {
            $html .= '<table>';
            
            // Get headers from first row if it's an array of arrays
            if (isset($reportData[0]) && is_array($reportData[0])) {
                $headers = array_keys($reportData[0]);
                $html .= '<thead><tr>';
                foreach ($headers as $header) {
                    $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                
                foreach ($reportData as $row) {
                    $html .= '<tr>';
                    foreach ($headers as $header) {
                        $value = $row[$header] ?? '';
                        $html .= '<td>' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</td>';
                    }
                    $html .= '</tr>';
                }
            } else {
                // Single row or simple data
                $html .= '<tbody>';
                foreach ($reportData as $key => $value) {
                    $html .= '<tr>';
                    $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</th>';
                    $html .= '<td>' . htmlspecialchars(is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value) . '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No data available for this report.</p>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
}

