<?php

namespace App\Actions\Attendance;

use App\Actions\Attendance\RegisterAttendanceRecordAction;
use App\Enums\AttendanceType;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarkAttendanceAction
{
    public function __construct(
        private MarkEntryAction $entry,
        private MarkExitAction $exit,
        private MarkBreakStartAction $breakStart,
        private MarkBreakEndAction $breakEnd,
        private RegisterAttendanceRecordAction $registerRecord,
    ) {}

    public function execute(Request $request): array
    {
        $tenantId = $request->user()->tenant_id;
        $type = AttendanceType::from($request->input('type'));

        $employee = Employee::where('tenant_id', $tenantId)
            ->where('employee_code', $request->input('employee_code'))
            ->first();

        if (! $employee) {
            throw new AttendanceException('El código de empleado no existe en este tenant.', 404);
        }

        if ($employee->employment_status !== 'active') {
            throw new AttendanceException('El empleado no está activo.', 403);
        }

        $tz = $employee->tenant?->timezone ?? config('app.timezone');
        $now = now();
        $today = $now->copy()->setTimezone($tz)->toDateString();

        return DB::transaction(function () use ($request, $type, $employee, $tenantId, $tz, $now, $today) {
            $session = AttendanceSession::firstOrNew([
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id,
                'attendance_date' => $today,
            ]);

            $session = match ($type) {
                AttendanceType::Entry => $this->entry->execute($session, $employee, $now, $tz),
                AttendanceType::Exit => $this->exit->execute($session, $employee, $now, $tz),
                AttendanceType::BreakStart => $this->breakStart->execute($session, $now),
                AttendanceType::BreakEnd => $this->breakEnd->execute($session, $now),
            };

            $this->registerRecord->execute($employee, $type, $now, $request);

            return [
                'message' => match ($type) {
                    AttendanceType::Entry => 'Marcación de ENTRADA registrada correctamente.',
                    AttendanceType::Exit => 'Marcación de SALIDA registrada correctamente.',
                    AttendanceType::BreakStart => 'Inicio de descanso registrado.',
                    AttendanceType::BreakEnd => 'Fin de descanso registrado correctamente.',
                },
                'employee' => $employee,
                'session' => $session->fresh(),
            ];
        });
    }
}
