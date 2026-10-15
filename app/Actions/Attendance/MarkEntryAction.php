<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use Carbon\Carbon;

class MarkEntryAction
{
    public function __construct(private FindScheduleDayAction $findScheduleDay) {}

    public function execute(AttendanceSession $session, Employee $employee, Carbon $now, string $tz): AttendanceSession
    {
        if ($session->exists && $session->check_in_at) {
            throw new AttendanceException('Ya registraste tu marcación de ENTRADA para el día de hoy.', 409);
        }

        $localNow = $now->copy()->setTimezone($tz);
        $today = $localNow->toDateString();
        $scheduleDay = $this->findScheduleDay->execute($employee, $today, $localNow->dayOfWeekIso);

        $lateMinutes = 0;
        $status = 'present';
        $scheduledMinutes = 0;

        if ($scheduleDay && $scheduleDay->is_working_day) {
            $scheduledIn = $this->at($today, $scheduleDay->check_in_time, $tz);
            $scheduledOut = $this->at($today, $scheduleDay->check_out_time, $tz);

            $breakMinutes = (int) ($scheduleDay->break_duration_minutes ?? 0);
            if (! empty($scheduleDay->break_start_time) && ! empty($scheduleDay->break_end_time)) {
                $breakMinutes = (int) $this->at($today, $scheduleDay->break_start_time, $tz)
                    ->diffInMinutes($this->at($today, $scheduleDay->break_end_time, $tz));
            }

            $scheduledMinutes = max(0, (int) $scheduledIn->diffInMinutes($scheduledOut) - $breakMinutes);

            $tolerance = $scheduleDay->check_in_tolerance_minutes ?? 0;
            if ($localNow->gt($scheduledIn->copy()->addMinutes($tolerance))) {
                $lateMinutes = (int) $scheduledIn->diffInMinutes($localNow);
                $status = 'late';
            }
        }

        $session->check_in_at = $now;
        $session->scheduled_minutes = $scheduledMinutes;
        $session->late_minutes = $lateMinutes;
        $session->status = $status;
        $session->save();

        return $session;
    }

    private function at(string $today, string $time, string $tz): Carbon
    {
        return Carbon::parse($today . ' ' . date('H:i:s', strtotime($time)), $tz);
    }
}
