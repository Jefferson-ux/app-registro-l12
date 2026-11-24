<?php

namespace App\Services;

use App\Enums\AttendanceType;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\WorkFlexibleRule;
use App\Models\WorkScheduleDay;
use App\Services\Attendance\Policies\FlexibleSchedulePolicy;
use App\Services\Attendance\SchedulePolicyResolver;
use Carbon\Carbon;

class AttendanceMarkingWindowService
{
    public const ENTRY_EARLY_WINDOW_MINUTES = 45;

    public const EXIT_EARLY_WINDOW_MINUTES = 45;

    public const BREAK_START_EARLY_WINDOW_MINUTES = 30;

    public const MINIMUM_BREAK_DURATION_MINUTES = 10;

    public function validate(Employee $employee, ?string $requestedType, ?WorkScheduleDay $scheduleDay): void
    {
        $policy = app(SchedulePolicyResolver::class)->forEmployee($employee);

        if ($policy instanceof FlexibleSchedulePolicy) {
            $this->validateFlexible($employee, $requestedType, $policy);

            return;
        }

        $timezone = $employee->tenant?->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);
        $today = $now->toDateString();

        $session = AttendanceSession::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($session) {
            $session->setRelation('employee', $employee);
        }

        $nextAction = $session?->nextAction() ?? AttendanceType::Entry->value;

        if ($nextAction === 'COMPLETED') {
            throw new AttendanceException('La jornada de hoy ya está completada.', 409);
        }

        $type = filled($requestedType)
            ? AttendanceType::from($requestedType)
            : AttendanceType::from($nextAction);

        if ($type->value !== $nextAction) {
            throw new AttendanceException('La marcación no corresponde al siguiente paso de la jornada.', 409);
        }

        $this->validateTiming($type, $scheduleDay, $session, $now, $timezone);
    }

    public function validateTiming(
        AttendanceType $type,
        ?WorkScheduleDay $scheduleDay,
        ?AttendanceSession $session,
        Carbon $now,
        string $timezone,
    ): void {
        // Horario flexible sin día configurado: no hay ventana horaria que validar.
        if ($scheduleDay === null) {
            return;
        }

        $today = $now->toDateString();

        match ($type) {
            AttendanceType::Entry => $this->ensureNotTooEarly(
                $now,
                $this->scheduledTime($scheduleDay, 'check_in_time', $today, $timezone)
                    ->subMinutes(self::ENTRY_EARLY_WINDOW_MINUTES),
                'La entrada solo se puede marcar dentro de los '.self::ENTRY_EARLY_WINDOW_MINUTES.' minutos previos al horario.'
            ),
            AttendanceType::Exit => $this->ensureNotTooEarly(
                $now,
                $this->scheduledTime($scheduleDay, 'check_out_time', $today, $timezone)
                    ->subMinutes(self::EXIT_EARLY_WINDOW_MINUTES),
                'La salida solo se puede marcar dentro de los '.self::EXIT_EARLY_WINDOW_MINUTES.' minutos previos al horario.'
            ),
            AttendanceType::BreakStart => $this->validateBreakStart($scheduleDay, $now, $today, $timezone),
            AttendanceType::BreakEnd => $this->validateBreakEnd($session, $now, $timezone),
        };
    }

    private function validateBreakStart(
        WorkScheduleDay $scheduleDay,
        Carbon $now,
        string $today,
        string $timezone,
    ): void {
        $breakStart = $this->scheduledTime($scheduleDay, 'break_start_time', $today, $timezone);
        $breakEnd = $this->scheduledTime($scheduleDay, 'break_end_time', $today, $timezone);
        $earliestStart = $breakStart->copy()->subMinutes(self::BREAK_START_EARLY_WINDOW_MINUTES);
        $latestStart = $breakEnd->copy()->subMinutes(self::MINIMUM_BREAK_DURATION_MINUTES);

        if ($earliestStart->gt($latestStart)) {
            throw new AttendanceException('El horario configurado no permite un descanso de duración válida.', 422);
        }

        if ($now->lt($earliestStart)) {
            throw new AttendanceException(
                'El descanso solo se puede iniciar dentro de los '.self::BREAK_START_EARLY_WINDOW_MINUTES.' minutos previos al horario.',
                422
            );
        }

        if ($now->gt($latestStart)) {
            throw new AttendanceException(
                'El descanso debe iniciarse al menos ' . self::MINIMUM_BREAK_DURATION_MINUTES . ' minutos antes de su hora de fin programada.',
                422
            );
        }
    }

    private function validateBreakEnd(?AttendanceSession $session, Carbon $now, string $timezone): void
    {
        if (! $session?->break_start_at) {
            throw new AttendanceException('Debe iniciar el descanso antes de marcar su fin.', 422);
        }

        $breakStartedAt = Carbon::parse($session->break_start_at)->setTimezone($timezone);
        $earliestEnd = $breakStartedAt->copy()->addMinutes(self::MINIMUM_BREAK_DURATION_MINUTES);

        if ($now->lt($earliestEnd)) {
            throw new AttendanceException(
                'Debe transcurrir al menos '.self::MINIMUM_BREAK_DURATION_MINUTES.' minutos desde el inicio del descanso.',
                422
            );
        }
    }

    private function scheduledTime(
        WorkScheduleDay $scheduleDay,
        string $field,
        string $date,
        string $timezone,
    ): Carbon {
        $time = $scheduleDay->{$field};

        if (! $time) {
            throw new AttendanceException('Falta configurar una hora necesaria en el horario del empleado.', 422);
        }

        return Carbon::parse($date.' '.$time->format('H:i:s'), $timezone);
    }

    private function ensureNotTooEarly(Carbon $now, Carbon $earliestTime, string $message): void
    {
        if ($now->lt($earliestTime)) {
            throw new AttendanceException($message, 422);
        }
    }

    private function validateFlexible(Employee $employee, ?string $requestedType, FlexibleSchedulePolicy $policy): void
    {
        $timezone = $employee->tenant?->timezone ?? config('app.timezone');
        $now = Carbon::now($timezone);

        $session = $this->findOpenFlexibleSession($employee);

        if ($session) {
            $session->setRelation('employee', $employee);
            $deadline = $policy->closingDeadline($employee, $now->copy(), $session);

            if ($deadline !== null && $now->gt($deadline)) {
                // Turno abandonado: cierre perezoso sin inventar salida.
                $session->update(['status' => 'incomplete']);
                $session = null;
            }
        }

        if (! $session) {
            $session = AttendanceSession::query()
                ->where('tenant_id', $employee->tenant_id)
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->first();

            if ($session) {
                $session->setRelation('employee', $employee);
            }
        }

        $expected = $policy->expectedNextAction($employee, $session);

        if ($expected === 'COMPLETED') {
            throw new AttendanceException('La jornada de hoy ya está completada.', 409);
        }

        $type = filled($requestedType)
            ? AttendanceType::from($requestedType)
            : AttendanceType::from($expected);

        if ($type->value !== $expected) {
            throw new AttendanceException('La marcación no corresponde al siguiente paso de la jornada.', 409);
        }

        $rule = $policy->rules($employee);

        match ($type) {
            AttendanceType::Entry => $policy->validateEntry($employee, $now),
            AttendanceType::BreakStart => null,
            AttendanceType::BreakEnd => $this->validateFlexibleBreakEnd($session, $now, $rule),
            AttendanceType::Exit => $this->validateFlexibleExit($session, $rule),
        };
    }

    private function findOpenFlexibleSession(Employee $employee): ?AttendanceSession
    {
        return AttendanceSession::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->whereNotIn('status', ['incomplete', 'absent'])
            ->orderByDesc('check_in_at')
            ->first();
    }

    private function validateFlexibleBreakEnd(
        ?AttendanceSession $session,
        Carbon $now,
        ?WorkFlexibleRule $rule,
    ): void {
        if (! $session?->break_start_at) {
            throw new AttendanceException('Debe iniciar el descanso antes de marcar su fin.', 422);
        }

        if (! $rule) {
            return;
        }

        $elapsed = Carbon::parse($session->break_start_at)->diffInMinutes($now);

        if ($rule->min_break_minutes !== null && $elapsed < $rule->min_break_minutes) {
            throw new AttendanceException("El descanso debe durar al menos {$rule->min_break_minutes} minutos.", 422);
        }

        if ($rule->max_break_minutes !== null && $elapsed > $rule->max_break_minutes) {
            throw new AttendanceException("El descanso no puede superar los {$rule->max_break_minutes} minutos.", 422);
        }
    }

    private function validateFlexibleExit(?AttendanceSession $session, ?WorkFlexibleRule $rule): void
    {
        if (! $session || ! $session->check_in_at) {
            throw new AttendanceException('No existe una marcación de entrada previa.', 422);
        }

        if ($rule?->requires_break && ! $session->break_end_at) {
            throw new AttendanceException('Debe completar su descanso antes de marcar la salida.', 422);
        }
    }
}
