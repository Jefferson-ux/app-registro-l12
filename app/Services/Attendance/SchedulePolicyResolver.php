<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Services\Attendance\Contracts\SchedulePolicy;
use App\Services\Attendance\Policies\FixedSchedulePolicy;
use App\Services\Attendance\Policies\FlexibleSchedulePolicy;
use App\Services\Attendance\Policies\UnsupportedSchedulePolicy;

class SchedulePolicyResolver
{
    public function for(?string $scheduleType): SchedulePolicy
    {
        return match ($scheduleType) {
            null, 'fixed' => new FixedSchedulePolicy(),
            'flexible' => new FlexibleSchedulePolicy(),
            default => new UnsupportedSchedulePolicy($scheduleType),
        };
    }

    public function forEmployee(Employee $employee): SchedulePolicy
    {
        return $this->for($employee->activeSchedule()?->workSchedule?->schedule_type);
    }
}
