<?php

namespace App\Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreAttendanceRecordRequest;
use App\Modules\HR\Http\Requests\UpdateAttendanceRecordRequest;
use App\Modules\HR\Http\Resources\AttendanceRecordResource;
use App\Modules\HR\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceRecordController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AttendanceRecord::class);

        $query = AttendanceRecord::with(['employee'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        return AttendanceRecordResource::collection($query->latest()->paginate());
    }

    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $this->authorize('create', AttendanceRecord::class);

        $record = AttendanceRecord::create(array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id]
        ));

        return response()->json([
            'message' => 'Attendance record created successfully.',
            'data' => new AttendanceRecordResource($record->load(['employee'])),
        ], 201);
    }

    public function show(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('view', $attendanceRecord);

        return response()->json([
            'data' => new AttendanceRecordResource($attendanceRecord->load(['employee'])),
        ]);
    }

    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->validated());

        return response()->json([
            'message' => 'Attendance record updated successfully.',
            'data' => new AttendanceRecordResource($attendanceRecord->load(['employee'])),
        ]);
    }

    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->json([
            'message' => 'Attendance record deleted successfully.',
        ]);
    }
}

