<?php

namespace App\Services\Attendance\Contracts;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\WorkScheduleDay;
use Carbon\Carbon;

interface SchedulePolicy
{
    public function worksOn(Employee $employee, Carbon $date): bool;

    public function scheduleDay(Employee $employee, Carbon $date): ?WorkScheduleDay;

    public function enforcesTimeWindow(): bool;

    public function blockedReason(): ?string;

    public function closingDeadline(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon;

    public function marksAbsences(): bool;

    public function validateEntry(Employee $employee, Carbon $now): void;
}
