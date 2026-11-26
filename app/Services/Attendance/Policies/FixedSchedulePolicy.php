<?php

namespace App\Services\Attendance\Policies;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\WorkScheduleDay;
use App\Services\Attendance\Contracts\SchedulePolicy;
use Carbon\Carbon;

class FixedSchedulePolicy implements SchedulePolicy
{
    public function timezone(Employee $employee): string
    {
        return $employee->tenant?->timezone ?? config('app.timezone');
    }

    public function worksOn(Employee $employee, Carbon $date): bool
    {
        $day = $this->scheduleDay($employee, $date);

        return $day !== null && (bool) $day->is_working_day;
    }

    public function scheduleDay(Employee $employee, Carbon $date): ?WorkScheduleDay
    {
        return $employee->activeSchedule()
            ?->workSchedule
            ?->days()
            ->where('day_of_week', $date->dayOfWeekIso)
            ->first();
    }

    public function blockedReason(): ?string
    {
        return null;
    }

    public function closingDeadline(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon
    {
        $targetDate = $session?->attendance_date
            ? Carbon::parse($session->attendance_date)->toDateString()
            : $date->toDateString();

        $day = $this->scheduleDay($employee, Carbon::parse($targetDate));

        if (! $day || ! $day->is_working_day) {
            return null;
        }

        $checkOut = $this->checkOutAt($day, $targetDate, $this->timezone($employee));

        if ($checkOut === null) {
            return null;
        }

        return $checkOut->copy()->addMinutes((int) ($day->auto_close_after_minutes ?? 0));
    }

    public function closingCheckOutAt(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon
    {
        $targetDate = $session?->attendance_date
            ? Carbon::parse($session->attendance_date)->toDateString()
            : $date->toDateString();

        $day = $this->scheduleDay($employee, Carbon::parse($targetDate));

        if (! $day || ! $day->is_working_day) {
            return null;
        }

        return $this->checkOutAt($day, $targetDate, $this->timezone($employee));
    }

    public function marksAbsences(): bool
    {
        return true;
    }

    public function marksAbsenceOn(Employee $employee, Carbon $date, Carbon $now): bool
    {
        if (! $this->marksAbsences() || ! $this->worksOn($employee, $date)) {
            return false;
        }

        $deadline = $this->closingDeadline($employee, $date, null);

        if ($deadline === null) {
            return false;
        }

        return $now->gte($deadline);
    }

    public function scheduledMinutesFor(Employee $employee, Carbon $date): int
    {
        $day = $this->scheduleDay($employee, $date);

        if (! $day || ! $day->check_in_time || ! $day->check_out_time) {
            return 0;
        }

        $tz = $this->timezone($employee);
        $dateStr = $date->toDateString();

        $start = $this->scheduledDateTime($day, $dateStr, 'check_in_time', $tz);
        $end = $this->scheduledDateTime($day, $dateStr, 'check_out_time', $tz);

        if ($start === null || $end === null) {
            return 0;
        }

        $minutes = $start->diffInMinutes($end);

        if ($day->break_start_time && $day->break_end_time) {
            $breakStart = $this->scheduledDateTime($day, $dateStr, 'break_start_time', $tz);
            $breakEnd = $this->scheduledDateTime($day, $dateStr, 'break_end_time', $tz);

            if ($breakStart !== null && $breakEnd !== null) {
                $minutes -= $breakStart->diffInMinutes($breakEnd);
            }
        }

        return max(0, $minutes);
    }

    public function isNightShift(?WorkScheduleDay $day): bool
    {
        if (! $day?->check_in_time || ! $day->check_out_time) {
            return false;
        }

        return $day->check_out_time->format('H:i:s') < $day->check_in_time->format('H:i:s');
    }

    /**
     * Busca la sesión que corresponde a la marcación actual. Para turnos nocturnos
     * que cruzan medianoche, devuelve la sesión abierta del día anterior.
     */
    public function sessionForMarking(Employee $employee, Carbon $now): ?AttendanceSession
    {
        $tz = $this->timezone($employee);
        $today = $now->copy()->setTimezone($tz)->toDateString();

        $session = AttendanceSession::withoutGlobalScopes()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($session) {
            return $session;
        }

        $yesterday = Carbon::parse($today, $tz)->subDay()->toDateString();
        $day = $this->scheduleDay($employee, Carbon::parse($yesterday, $tz));

        if (! $this->isNightShift($day)) {
            return null;
        }

        return AttendanceSession::withoutGlobalScopes()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $yesterday)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->whereNotIn('status', ['incomplete', 'absent'])
            ->first();
    }

    public function scheduledDateTime(
        WorkScheduleDay $day,
        string $date,
        string $field,
        string $timezone,
    ): ?Carbon {
        $time = $day->{$field} ?? null;

        if (! $time) {
            return null;
        }

        $value = $this->dateTimeOn($date, $time, $timezone);

        if ($field !== 'check_in_time' && $day->check_in_time) {
            $checkIn = $this->dateTimeOn($date, $day->check_in_time, $timezone);

            if ($value->lt($checkIn)) {
                $value->addDay();
            }
        }

        return $value;
    }

    private function checkOutAt(WorkScheduleDay $day, string $date, string $timezone): ?Carbon
    {
        return $this->scheduledDateTime($day, $date, 'check_out_time', $timezone);
    }

    private function dateTimeOn(string $date, \DateTimeInterface|string $time, string $timezone): Carbon
    {
        return Carbon::parse($date . ' ' . Carbon::parse($time)->format('H:i:s'), $timezone);
    }
}
