<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Services\Attendance\Policies\FixedSchedulePolicy;
use App\Services\Attendance\Policies\FlexibleSchedulePolicy;
use App\Services\Attendance\SchedulePolicyResolver;
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
        $policy = app(SchedulePolicyResolver::class)->forEmployee($employee);

        $lateMinutes = 0;
        $status = 'present';
        $scheduledMinutes = 0;

        if ($scheduleDay && $scheduleDay->is_working_day) {
            $scheduledIn = $this->at($today, $scheduleDay->check_in_time, $tz);
            $scheduledOut = $policy instanceof FixedSchedulePolicy
                ? $policy->scheduledDateTime($scheduleDay, $today, 'check_out_time', $tz)
                : $this->at($today, $scheduleDay->check_out_time, $tz);

            $breakMinutes = 0;
            if (! empty($scheduleDay->break_start_time) && ! empty($scheduleDay->break_end_time)) {
                $breakStart = $policy instanceof FixedSchedulePolicy
                    ? $policy->scheduledDateTime($scheduleDay, $today, 'break_start_time', $tz)
                    : $this->at($today, $scheduleDay->break_start_time, $tz);
                $breakEnd = $policy instanceof FixedSchedulePolicy
                    ? $policy->scheduledDateTime($scheduleDay, $today, 'break_end_time', $tz)
                    : $this->at($today, $scheduleDay->break_end_time, $tz);

                if ($breakStart !== null && $breakEnd !== null) {
                    $breakMinutes = (int) $breakStart->diffInMinutes($breakEnd);
                }
            }

            if ($scheduledOut !== null) {
                $scheduledMinutes = max(0, (int) $scheduledIn->diffInMinutes($scheduledOut) - $breakMinutes);
            }

            $tolerance = $scheduleDay->check_in_tolerance_minutes ?? 0;
            if ($localNow->gt($scheduledIn->copy()->addMinutes($tolerance))) {
                $lateMinutes = (int) $scheduledIn->diffInMinutes($localNow);
                $status = 'late';
            }
        } elseif ($policy instanceof FlexibleSchedulePolicy) {
            $scheduledMinutes = (int) ($policy->rules($employee)?->min_daily_minutes ?? 0);
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
