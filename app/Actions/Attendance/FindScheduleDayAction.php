<?php

namespace App\Actions\Attendance;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\WorkScheduleDay;

class FindScheduleDayAction
{
    public function execute(Employee $employee, string $today, int $dayOfWeek): ?WorkScheduleDay
    {
        return EmployeeSchedule::where('employee_id', $employee->id)
            ->where('status', true)
            ->where('start_date', '<=', $today)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->with(['workSchedule.days' => fn($q) => $q->where('day_of_week', $dayOfWeek)])
            ->first()?->workSchedule?->days->first();
    }
}
