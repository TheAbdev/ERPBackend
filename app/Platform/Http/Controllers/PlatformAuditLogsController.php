<?php

namespace App\Platform\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PlatformAuditLogsController extends Controller
{
    /**
     * List audit logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->leftJoin('tenants', 'audit_logs.tenant_id', '=', 'tenants.id')
            ->select(
                'audit_logs.*',
                'users.name as user_name',
                'users.email as user_email',
                'tenants.name as tenant_name'
            );

        // Apply filters
        if ($request->has('user_id')) {
            $query->where('audit_logs.user_id', $request->input('user_id'));
        }
        if ($request->has('tenant_id')) {
            $query->where('audit_logs.tenant_id', $request->input('tenant_id'));
        }
        if ($request->has('action')) {
            $query->where('audit_logs.action', $request->input('action'));
        }
        if ($request->has('model_type')) {
            $query->where('audit_logs.model_type', $request->input('model_type'));
        }
        if ($request->has('date_from')) {
            $query->where('audit_logs.created_at', '>=', Carbon::parse($request->input('date_from')));
        }
        if ($request->has('date_to')) {
            $query->where('audit_logs.created_at', '<=', Carbon::parse($request->input('date_to')));
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $total = $query->count();
        $logs = $query->orderBy('audit_logs.created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user_name ?? 'Unknown',
                'user_email' => $log->user_email ?? '',
                'tenant_id' => $log->tenant_id,
                'tenant_name' => $log->tenant_name ?? null,
                'action' => $log->action,
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'changes' => json_decode($log->changes ?? '{}', true),
                'ip_address' => $log->ip_address ?? null,
                'user_agent' => $log->user_agent ?? null,
                'created_at' => $log->created_at,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get audit log statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $today = Carbon::today();
        
        $totalActionsToday = DB::table('audit_logs')
            ->whereDate('created_at', $today)
            ->count();

        $mostActiveUser = DB::table('audit_logs')
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.user_id', 'users.name as user_name', DB::raw('COUNT(*) as actions_count'))
            ->whereDate('audit_logs.created_at', $today)
            ->groupBy('audit_logs.user_id', 'users.name')
            ->orderBy('actions_count', 'desc')
            ->first();

        $mostChangedModel = DB::table('audit_logs')
            ->select('model_type', DB::raw('COUNT(*) as changes_count'))
            ->whereDate('created_at', $today)
            ->groupBy('model_type')
            ->orderBy('changes_count', 'desc')
            ->first();

        return response()->json([
            'data' => [
                'total_actions_today' => $totalActionsToday,
                'most_active_user' => $mostActiveUser ? [
                    'user_id' => $mostActiveUser->user_id,
                    'user_name' => $mostActiveUser->user_name,
                    'actions_count' => $mostActiveUser->actions_count,
                ] : null,
                'most_changed_model' => $mostChangedModel ? [
                    'model_type' => $mostChangedModel->model_type,
                    'changes_count' => $mostChangedModel->changes_count,
                ] : null,
            ],
        ]);
    }

    /**
     * Export audit logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $format = $request->input('format', 'csv');
        
        // Get audit logs data
        $query = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->leftJoin('tenants', 'audit_logs.tenant_id', '=', 'tenants.id')
            ->select(
                'audit_logs.*',
                'users.name as user_name',
                'users.email as user_email',
                'tenants.name as tenant_name'
            );

        // Apply filters (only if they have values)
        if ($request->filled('user_id')) {
            $query->where('audit_logs.user_id', $request->input('user_id'));
        }
        if ($request->filled('tenant_id')) {
            $query->where('audit_logs.tenant_id', $request->input('tenant_id'));
        }
        if ($request->filled('action')) {
            $query->where('audit_logs.action', $request->input('action'));
        }
        if ($request->filled('model_type')) {
            $query->where('audit_logs.model_type', $request->input('model_type'));
        }
        if ($request->filled('date_from')) {
            $query->where('audit_logs.created_at', '>=', Carbon::parse($request->input('date_from')));
        }
        if ($request->filled('date_to')) {
            $query->where('audit_logs.created_at', '<=', Carbon::parse($request->input('date_to')));
        }

        $logs = $query->orderBy('audit_logs.created_at', 'desc')->get();
        
        \Log::info('Audit logs export', [
            'format' => $format,
            'count' => $logs->count(),
            'filters' => $request->only(['user_id', 'tenant_id', 'action', 'model_type', 'date_from', 'date_to']),
        ]);

        // Prepare data for export
        $exportData = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user_name' => $log->user_name ?? 'Unknown',
                'user_email' => $log->user_email ?? '',
                'tenant_name' => $log->tenant_name ?? 'N/A',
                'action' => $log->action,
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'ip_address' => $log->ip_address ?? '',
                'created_at' => $log->created_at,
            ];
        })->toArray();
        
        \Log::info('Export data prepared', [
            'data_count' => count($exportData),
            'first_row' => $exportData[0] ?? null,
        ]);

        // Generate file
        $fileName = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
        $filePath = 'reports/' . $fileName;
        
        // Ensure reports directory exists
        Storage::disk('public')->makeDirectory('reports');
        
        // Generate file content based on format
        $fileContent = $this->formatExportContent($exportData, $format);
        Storage::disk('public')->put($filePath, $fileContent);
        
        // Return download URL
        $downloadUrl = '/api/platform/reports/download/' . basename($filePath);

        return response()->json([
            'data' => [
                'download_url' => $downloadUrl,
            ],
        ]);
    }

    /**
     * Format export content.
     *
     * @param  array  $data
     * @param  string  $format
     * @return string
     */
    protected function formatExportContent(array $data, string $format): string
    {
        switch ($format) {
            case 'pdf':
                return $this->generatePdf($data);
            case 'csv':
            default:
                return $this->generateCsv($data);
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
        if (empty($data)) {
            \Log::warning('Empty data array passed to generateCsv');
            return 'No data available';
        }

        $output = fopen('php://temp', 'r+');
        
        // Add headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($data as $row) {
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
        
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);
        
        \Log::info('CSV generated', [
            'rows' => count($data),
            'size' => strlen($csvString),
        ]);
        
        return $csvString;
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
            $pdf->setPaper('a4', 'landscape');
            return $pdf->output();
        } catch (\Exception $e) {
            \Log::error('PDF generation failed for audit logs', [
                'error' => $e->getMessage(),
            ]);
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
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Audit Logs Export</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 10px; }
        h1 { color: #333; border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .meta { margin-bottom: 20px; color: #666; }
    </style>
</head>
<body>
    <h1>Audit Logs Export</h1>
    <div class="meta">
        <p><strong>Generated At:</strong> ' . now()->toIso8601String() . '</p>
        <p><strong>Total Records:</strong> ' . count($data) . '</p>
    </div>';
        
        if (!empty($data)) {
            $html .= '<table>';
            $headers = array_keys($data[0]);
            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($headers as $header) {
                    $value = $row[$header] ?? '';
                    $html .= '<td>' . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>No audit logs found.</p>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
}

