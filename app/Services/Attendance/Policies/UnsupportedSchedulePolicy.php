<?php

namespace App\Services\Attendance\Policies;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\WorkScheduleDay;
use App\Services\Attendance\Contracts\SchedulePolicy;
use Carbon\Carbon;

class UnsupportedSchedulePolicy implements SchedulePolicy
{
    public function __construct(private string $scheduleType) {}

    public function timezone(Employee $employee): string
    {
        return $employee->tenant?->timezone ?? config('app.timezone');
    }

    public function worksOn(Employee $employee, Carbon $date): bool
    {
        return false;
    }

    public function scheduleDay(Employee $employee, Carbon $date): ?WorkScheduleDay
    {
        return null;
    }

    public function blockedReason(): ?string
    {
        $label = $this->scheduleType === 'rotating' ? 'rotativo' : $this->scheduleType;

        return "El horario asignado es de tipo '{$label}' y aún no está disponible para registrar asistencia. Contacta al administrador.";
    }

    public function closingDeadline(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon
    {
        return null;
    }

    public function closingCheckOutAt(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon
    {
        return null;
    }

    public function marksAbsences(): bool
    {
        return false;
    }

    public function marksAbsenceOn(Employee $employee, Carbon $date, Carbon $now): bool
    {
        return false;
    }

    public function scheduledMinutesFor(Employee $employee, Carbon $date): int
    {
        return 0;
    }
}
