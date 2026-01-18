<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\AuditLog;
use App\Modules\ERP\Http\Resources\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::where('tenant_id', $request->user()->tenant_id)
            ->with(['user:id,name,email']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter by model type (module)
        if ($request->has('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        // Filter by model ID
        if ($request->has('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        // Filter by module (extract from model_type)
        if ($request->has('module')) {
            $module = $request->input('module');
            $moduleNamespace = "App\\Modules\\{$module}\\Models\\";
            $query->where('model_type', 'like', $moduleNamespace . '%');
        }

        // Date range filtering
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Search in description/metadata
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('model_name', 'like', "%{$search}%")
                    ->orWhereJsonContains('metadata->description', $search);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json(AuditLogResource::collection($logs));
    }

    /**
     * Display the specified audit log.
     *
     * @param  \App\Core\Models\AuditLog  $auditLog
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);

        $auditLog->load(['user', 'model']);

        return response()->json([
            'data' => new AuditLogResource($auditLog),
        ]);
    }

    /**
     * Export audit logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $this->authorize('export', AuditLog::class);

        $format = $request->input('format', 'json'); // json or csv

        $query = AuditLog::where('tenant_id', $request->user()->tenant_id)
            ->with(['user:id,name,email']);

        // Apply same filters as index
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->has('model_id')) {
            $query->where('model_id', $request->input('model_id'));
        }

        if ($request->has('module')) {
            $module = $request->input('module');
            $moduleNamespace = "App\\Modules\\{$module}\\Models\\";
            $query->where('model_type', 'like', $moduleNamespace . '%');
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'csv') {
            return $this->exportCsv($logs);
        }

        return response()->json([
            'data' => AuditLogResource::collection($logs),
            'meta' => [
                'total' => $logs->count(),
                'exported_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Export audit logs as CSV.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $logs
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function exportCsv($logs)
    {
        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($logs) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID',
                'Date',
                'User',
                'Action',
                'Model Type',
                'Model Name',
                'Model ID',
                'IP Address',
                'User Agent',
                'Description',
            ]);

            // CSV Rows
            foreach ($logs as $log) {
                $description = $log->metadata['description'] ?? '';

                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? $log->user->name . ' (' . $log->user->email . ')' : 'System',
                    $log->action,
                    $log->model_type,
                    $log->model_name,
                    $log->model_id,
                    $log->ip_address,
                    $log->user_agent,
                    $description,
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}

