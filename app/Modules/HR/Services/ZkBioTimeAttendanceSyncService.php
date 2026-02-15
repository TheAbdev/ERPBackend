<?php

namespace App\Modules\HR\Services;

use App\Core\Models\Tenant;
use App\Modules\HR\Models\Attendance;
use App\Modules\HR\Models\AttendanceRecord;
use App\Modules\HR\Models\Employee;
use Carbon\Carbon;
use RuntimeException;

class ZkBioTimeAttendanceSyncService
{
    public function __construct(
        protected ZkBioTimeClient $client
    ) {}

    public function syncTenant(Tenant $tenant, ?Carbon $from, ?Carbon $to, int $pageSize): array
    {
        $from = $from ?: $this->getDefaultFrom($tenant);
        $to = $to ?: now();

        $page = 1;
        $processed = 0;
        $created = 0;
        $skipped = 0;
        $missingEmployees = 0;

        do {
            $response = $this->client->getTransactions([
                'page' => $page,
                'page_size' => $pageSize,
                'start_time' => $from->format('Y-m-d H:i:s'),
                'end_time' => $to->format('Y-m-d H:i:s'),
            ]);

            if (($response['code'] ?? 0) !== 0) {
                $message = $response['msg'] ?? 'Unknown API error';
                throw new RuntimeException('ZKBioTime response error: ' . $message);
            }

            $data = $response['data'] ?? [];
            $count = (int) ($response['count'] ?? 0);

            foreach ($data as $transaction) {
                $processed++;

                $employee = $this->findEmployee($tenant->id, $transaction['emp_code'] ?? null);
                if (! $employee) {
                    $missingEmployees++;
                    continue;
                }

                $result = $this->storeTransaction($tenant->id, $employee, $transaction);
                if ($result === 'created') {
                    $created++;
                } else {
                    $skipped++;
                }
            }

            $page++;
        } while (($page - 1) * $pageSize < $count);

        $tenant->setSetting('zkbiotime.last_sync_at', $to->toIso8601String());
        $tenant->save();

        return [
            'tenant_id' => $tenant->id,
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'processed' => $processed,
            'created' => $created,
            'skipped' => $skipped,
            'missing_employees' => $missingEmployees,
        ];
    }

    protected function getDefaultFrom(Tenant $tenant): Carbon
    {
        $lastSync = $tenant->getSetting('zkbiotime.last_sync_at');
        if (! empty($lastSync)) {
            return Carbon::parse($lastSync);
        }

        return now()->subDay();
    }

    protected function findEmployee(int $tenantId, ?string $empCode): ?Employee
    {
        if (empty($empCode)) {
            return null;
        }

        return Employee::where('tenant_id', $tenantId)
            ->where('biotime_emp_code', $empCode)
            ->first();
    }

    protected function storeTransaction(int $tenantId, Employee $employee, array $transaction): string
    {
        $externalId = (string) ($transaction['id'] ?? '');
        if ($externalId === '') {
            return 'skipped';
        }

        $punchTime = $this->parsePunchTime($transaction['punch_time'] ?? null);
        if (! $punchTime) {
            return 'skipped';
        }

        $punchType = $this->resolvePunchType($transaction);
        $attendanceDate = $punchTime->toDateString();

        $attributes = [
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'source' => 'biotime',
            'external_id' => $externalId,
        ];

        $values = [
            'attendance_date' => $attendanceDate,
            'check_in' => $punchType === 'in' ? $punchTime : null,
            'check_out' => $punchType === 'out' ? $punchTime : null,
            'status' => $transaction['punch_state_display'] ?? 'present',
            'raw_payload' => $transaction,
        ];

        $record = AttendanceRecord::firstOrNew($attributes);
        $isNew = ! $record->exists;
        if ($isNew) {
            $record->fill($values);
            $record->save();
            $this->updateDailyAttendance($tenantId, $employee->id, $attendanceDate, $punchType, $punchTime);
            return 'created';
        }

        return 'skipped';
    }

    protected function updateDailyAttendance(int $tenantId, int $employeeId, string $date, ?string $punchType, Carbon $punchTime): void
    {
        if ($punchType === null) {
            return;
        }

        $attendance = Attendance::firstOrNew([
            'tenant_id' => $tenantId,
            'employee_id' => $employeeId,
            'attendance_date' => $date,
        ]);

        if (! $attendance->exists) {
            $attendance->status = 'present';
        }

        if ($punchType === 'in') {
            $attendance->check_in = $this->minDateTime($attendance->check_in, $punchTime);
        }

        if ($punchType === 'out') {
            $attendance->check_out = $this->maxDateTime($attendance->check_out, $punchTime);
        }

        $attendance->save();
    }

    protected function parsePunchTime(?string $punchTime): ?Carbon
    {
        if (empty($punchTime)) {
            return null;
        }

        $timezone = config('zkbiotime.timezone');

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $punchTime, $timezone);
        } catch (\Throwable $e) {
            try {
                return Carbon::parse($punchTime, $timezone);
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    protected function resolvePunchType(array $transaction): ?string
    {
        $display = strtolower((string) ($transaction['punch_state_display'] ?? ''));
        $state = (string) ($transaction['punch_state'] ?? '');

        if ($state === '0' || str_contains($display, 'check in')) {
            return 'in';
        }

        if ($state === '1' || str_contains($display, 'check out')) {
            return 'out';
        }

        return null;
    }

    protected function minDateTime($current, Carbon $candidate): Carbon
    {
        if (empty($current)) {
            return $candidate;
        }

        $currentCarbon = $current instanceof Carbon ? $current : Carbon::parse($current);
        return $candidate->lessThan($currentCarbon) ? $candidate : $currentCarbon;
    }

    protected function maxDateTime($current, Carbon $candidate): Carbon
    {
        if (empty($current)) {
            return $candidate;
        }

        $currentCarbon = $current instanceof Carbon ? $current : Carbon::parse($current);
        return $candidate->greaterThan($currentCarbon) ? $candidate : $currentCarbon;
    }
}
