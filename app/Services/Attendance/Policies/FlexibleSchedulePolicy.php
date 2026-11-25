<?php

namespace App\Services\Attendance\Policies;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\WorkFlexibleRule;
use App\Models\WorkScheduleDay;
use App\Services\Attendance\Contracts\SchedulePolicy;
use Carbon\Carbon;

class FlexibleSchedulePolicy implements SchedulePolicy
{
    public function timezone(Employee $employee): string
    {
        return $employee->tenant?->timezone ?? config('app.timezone');
    }

    public function worksOn(Employee $employee, Carbon $date): bool
    {
        return $employee->activeSchedule() !== null;
    }

    public function scheduleDay(Employee $employee, Carbon $date): ?WorkScheduleDay
    {
        return null;
    }

    public function blockedReason(): ?string
    {
        return null;
    }

    public function closingDeadline(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon
    {
        if (! $session?->check_in_at) {
            return null;
        }

        $rule = $this->rules($employee);

        if (! $rule || $rule->max_shift_minutes === null) {
            return null;
        }

        $tz = $this->timezone($employee);

        return Carbon::parse($session->check_in_at)
            ->setTimezone($tz)
            ->addMinutes((int) $rule->max_shift_minutes);
    }

    public function closingCheckOutAt(Employee $employee, Carbon $date, ?AttendanceSession $session): ?Carbon
    {
        return null;
    }

    public function marksAbsences(): bool
    {
        return ! empty($this->expectedDays($this->rules(app(Employee::class))));
    }

    public function marksAbsenceOn(Employee $employee, Carbon $date, Carbon $now): bool
    {
        $rule = $this->rules($employee);
        $expectedDays = $this->expectedDays($rule);

        if (empty($expectedDays)) {
            return false;
        }

        $tz = $this->timezone($employee);
        $localDate = $date->copy()->setTimezone($tz);
        $localNow = $now->copy()->setTimezone($tz);

        if (! in_array($localDate->dayOfWeekIso, $expectedDays, true)) {
            return false;
        }

        return $localNow->gt($localDate->copy()->endOfDay());
    }

    public function scheduledMinutesFor(Employee $employee, Carbon $date): int
    {
        return (int) ($this->rules($employee)?->min_daily_minutes ?? 0);
    }

    public function validateEntry(Employee $employee, Carbon $now): void
    {
        $rule = $this->rules($employee);

        if (! $rule) {
            return;
        }

        $timezone = $this->timezone($employee);

        $this->validateEntryWindow($rule, $now->copy()->setTimezone($timezone));
        $this->validateRestBetweenShifts($employee, $rule, $now);
    }

    public function rules(Employee $employee): ?WorkFlexibleRule
    {
        return $employee->activeSchedule()?->workSchedule?->flexibleRules;
    }

    public function requiresBreak(Employee $employee): bool
    {
        return (bool) ($this->rules($employee)?->requires_break ?? false);
    }

    public function expectedNextAction(Employee $employee, ?AttendanceSession $session): string
    {
        if (! $session || ! $session->check_in_at) {
            return 'ENTRY';
        }

        if ($session->check_out_at) {
            return 'COMPLETED';
        }

        if ($this->requiresBreak($employee)) {
            if (! $session->break_start_at) {
                return 'BREAK_START';
            }

            if (! $session->break_end_at) {
                return 'BREAK_END';
            }
        }

        return 'EXIT';
    }

    private function validateEntryWindow(WorkFlexibleRule $rule, Carbon $localNow): void
    {
        if ($rule->entry_window_start === null || $rule->entry_window_end === null) {
            return;
        }

        $startMinutes = $this->minutesOfDay((string) $rule->entry_window_start);
        $endMinutes = $this->minutesOfDay((string) $rule->entry_window_end);
        $nowMinutes = $localNow->hour * 60 + $localNow->minute;

        $inWindow = $startMinutes <= $endMinutes
            ? ($nowMinutes >= $startMinutes && $nowMinutes <= $endMinutes)
            : ($nowMinutes >= $startMinutes || $nowMinutes <= $endMinutes);

        if (! $inWindow) {
            $start = substr((string) $rule->entry_window_start, 0, 5);
            $end = substr((string) $rule->entry_window_end, 0, 5);

            throw new AttendanceException("La entrada solo se permite entre las {$start} y las {$end}.", 422);
        }
    }

    private function validateRestBetweenShifts(Employee $employee, WorkFlexibleRule $rule, Carbon $now): void
    {
        if ($rule->min_rest_between_shifts_minutes === null) {
            return;
        }

        $lastExit = AttendanceSession::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_out_at')
            ->orderByDesc('check_out_at')
            ->first();

        if (! $lastExit) {
            return;
        }

        $elapsed = Carbon::parse($lastExit->check_out_at)->diffInMinutes($now);

        if ($elapsed < $rule->min_rest_between_shifts_minutes) {
            throw new AttendanceException(
                "Debe descansar al menos {$rule->min_rest_between_shifts_minutes} minutos entre turnos.",
                422
            );
        }
    }

    private function minutesOfDay(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }

    /**
     * @return int[]
     */
    private function expectedDays(?WorkFlexibleRule $rule): array
    {
        if (! $rule || empty($rule->expected_days)) {
            return [];
        }

        return array_map('intval', (array) $rule->expected_days);
    }
}
