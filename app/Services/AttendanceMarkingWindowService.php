<?php

namespace App\Services;

use App\Enums\AttendanceType;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\WorkScheduleDay;
use Carbon\Carbon;

class AttendanceMarkingWindowService
{
    public const ENTRY_EARLY_WINDOW_MINUTES = 45;

    public const EXIT_EARLY_WINDOW_MINUTES = 45;

    public const BREAK_START_EARLY_WINDOW_MINUTES = 30;

    public const MINIMUM_BREAK_DURATION_MINUTES = 10;

    public function validate(Employee $employee, ?string $requestedType, WorkScheduleDay $scheduleDay): void
    {
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
        WorkScheduleDay $scheduleDay,
        ?AttendanceSession $session,
        Carbon $now,
        string $timezone,
    ): void {
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
}
