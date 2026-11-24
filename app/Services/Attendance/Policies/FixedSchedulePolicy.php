<?php

namespace App\Services\Attendance\Policies;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\WorkScheduleDay;
use App\Services\Attendance\Contracts\SchedulePolicy;
use Carbon\Carbon;

class FixedSchedulePolicy implements SchedulePolicy
{
    public function worksOn(Employee $employee, Carbon $date): bool
    {
        $day = $this->scheduleDay($employee, $date);

        return $day !== null && $day->is_working_day;
    }

    public function scheduleDay(Employee $employee, Carbon $date): ?WorkScheduleDay
    {
        return $employee->activeSchedule()
            ?->workSchedule
            ?->days()
            ->where('day_of_week', $date->dayOfWeekIso)
            ->first();
    }

    public function enforcesTimeWindow(): bool
    {
        return true;
    }

    public function blockedReason(): ?string
    {
        return null;
    }

    // TODO: Fixed (instancia de turno, turnos nocturnos, auto_close_after_minutes).
    public function closingDeadline(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon
    {
        return null;
    }

    public function marksAbsences(): bool
    {
        return true;
    }

    // TODO: Fixed (instancia de turno, turnos nocturnos, auto_close_after_minutes).
    public function validateEntry(Employee $employee, Carbon $now): void
    {
        // Placeholder: conserva el comportamiento actual; la ventana de Fixed se valida en AttendanceMarkingWindowService.
    }
}
