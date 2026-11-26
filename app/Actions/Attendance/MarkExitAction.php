<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Services\Attendance\Policies\FixedSchedulePolicy;
use App\Services\Attendance\SchedulePolicyResolver;
use Carbon\Carbon;

class MarkExitAction
{
    public function __construct(private FindScheduleDayAction $findScheduleDay) {}

    public function execute(AttendanceSession $session, Employee $employee, Carbon $now, string $tz): AttendanceSession
    {
        if (! $session->exists || ! $session->check_in_at) {
            throw new AttendanceException('No existe una marcación de entrada previa para el día de hoy.', 422);
        }

        if ($session->check_out_at) {
            throw new AttendanceException('Ya registraste tu marcación de SALIDA para el día de hoy.', 409);
        }

        $localNow = $now->copy()->setTimezone($tz);
        $shiftDate = $session->attendance_date
            ? Carbon::parse($session->attendance_date)->toDateString()
            : $localNow->toDateString();
        $scheduleDay = $this->findScheduleDay->execute(
            $employee,
            $shiftDate,
            Carbon::parse($shiftDate)->dayOfWeekIso
        );

        $workedMinutes = (int) Carbon::parse($session->check_in_at)->diffInMinutes($now);

        if ($session->break_start_at && $session->break_end_at) {
            $breakMinutes = (int) Carbon::parse($session->break_start_at)
                ->diffInMinutes(Carbon::parse($session->break_end_at));
            $workedMinutes = max(0, $workedMinutes - $breakMinutes);
        }

        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;

        if ($scheduleDay && $scheduleDay->is_working_day) {
            $policy = app(SchedulePolicyResolver::class)->forEmployee($employee);
            $scheduledOut = $policy instanceof FixedSchedulePolicy
                ? $policy->scheduledDateTime($scheduleDay, $shiftDate, 'check_out_time', $tz)
                : $this->at($shiftDate, $scheduleDay->check_out_time, $tz);

            if ($scheduledOut !== null) {
                $tolerance = $scheduleDay->check_out_tolerance_minutes ?? 0;

                if ($localNow->lt($scheduledOut->copy()->subMinutes($tolerance))) {
                    $earlyLeaveMinutes = (int) $localNow->diffInMinutes($scheduledOut);
                } elseif ($localNow->gt($scheduledOut)) {
                    $overtimeMinutes = (int) $scheduledOut->diffInMinutes($localNow);
                }
            }
        }

        $session->update([
            'check_out_at' => $now,
            'worked_minutes' => $workedMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        return $session;
    }

    private function at(string $today, string $time, string $tz): Carbon
    {
        return Carbon::parse($today . ' ' . date('H:i:s', strtotime($time)), $tz);
    }
}