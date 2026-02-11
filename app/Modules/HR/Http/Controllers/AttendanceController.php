<?php

namespace App\Modules\HR\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\HR\Http\Requests\StoreAttendanceRequest;
use App\Modules\HR\Http\Requests\UpdateAttendanceRequest;
use App\Modules\HR\Http\Resources\AttendanceResource;
use App\Modules\HR\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with(['employee'])
            ->where('tenant_id', $request->user()->tenant_id);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->has('date')) {
            $query->whereDate('attendance_date', $request->input('date'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $attendances = $query->latest('attendance_date')->paginate();

        return AttendanceResource::collection($attendances);
    }

    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        $this->authorize('create', Attendance::class);

        $attendance = Attendance::create($request->validated());

        event(new EntityCreated($attendance, $request->user()->id));

        return response()->json([
            'message' => 'Attendance created successfully.',
            'data' => new AttendanceResource($attendance->load(['employee'])),
        ], 201);
    }

    public function show(Attendance $attendance): JsonResponse
    {
        $this->authorize('view', $attendance);

        return response()->json([
            'data' => new AttendanceResource($attendance->load(['employee'])),
        ]);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        $this->authorize('update', $attendance);

        $attendance->update($request->validated());

        event(new EntityUpdated($attendance->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Attendance updated successfully.',
            'data' => new AttendanceResource($attendance->load(['employee'])),
        ]);
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $this->authorize('delete', $attendance);

        event(new EntityDeleted($attendance, request()->user()->id));

        $attendance->delete();

        return response()->json([
            'message' => 'Attendance deleted successfully.',
        ]);
    }
}

