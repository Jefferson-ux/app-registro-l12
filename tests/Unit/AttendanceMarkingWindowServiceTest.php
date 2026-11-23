<?php

use App\Enums\AttendanceType;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\WorkScheduleDay;
use App\Services\AttendanceMarkingWindowService;
use Carbon\Carbon;

uses(Tests\TestCase::class);

it('permite la entrada en el inicio de su ventana', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::Entry,
        new WorkScheduleDay(['check_in_time' => '08:00:00']),
        null,
        Carbon::parse('2026-11-18 07:15:00', 'America/Lima'),
        'America/Lima',
    ))->not->toThrow(AttendanceException::class);
});

it('rechaza la entrada antes de su ventana', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::Entry,
        new WorkScheduleDay(['check_in_time' => '08:00:00']),
        null,
        Carbon::parse('2026-11-18 07:14:59', 'America/Lima'),
        'America/Lima',
    ))->toThrow(AttendanceException::class);
});

it('rechaza la salida antes de su ventana', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::Exit,
        new WorkScheduleDay(['check_out_time' => '17:00:00']),
        null,
        Carbon::parse('2026-11-18 16:14:59', 'America/Lima'),
        'America/Lima',
    ))->toThrow(AttendanceException::class);
});

it('permite la salida en el inicio de su ventana', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::Exit,
        new WorkScheduleDay(['check_out_time' => '17:00:00']),
        null,
        Carbon::parse('2026-11-18 16:15:00', 'America/Lima'),
        'America/Lima',
    ))->not->toThrow(AttendanceException::class);
});

it('permite iniciar el descanso dentro de su ventana', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::BreakStart,
        new WorkScheduleDay([
            'break_start_time' => '10:00:00',
            'break_end_time' => '10:30:00',
        ]),
        null,
        Carbon::parse('2026-11-18 09:30:00', 'America/Lima'),
        'America/Lima',
    ))->not->toThrow(AttendanceException::class);
});

it('rechaza iniciar el descanso cuando ya no alcanza la duración mínima', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::BreakStart,
        new WorkScheduleDay([
            'break_start_time' => '10:00:00',
            'break_end_time' => '10:30:00',
        ]),
        null,
        Carbon::parse('2026-11-18 10:21:00', 'America/Lima'),
        'America/Lima',
    ))->toThrow(AttendanceException::class);
});

it('rechaza terminar el descanso antes de la duración mínima configurada', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::BreakEnd,
        new WorkScheduleDay,
        new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']),
        Carbon::parse('2026-11-18 12:09:59', 'America/Lima'),
        'America/Lima',
    ))->toThrow(AttendanceException::class);
});

it('permite terminar el descanso después de la duración mínima', function (): void {
    expect(fn() => (new AttendanceMarkingWindowService)->validateTiming(
        AttendanceType::BreakEnd,
        new WorkScheduleDay,
        new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']),
        Carbon::parse('2026-11-18 12:10:00', 'America/Lima'),
        'America/Lima',
    ))->not->toThrow(AttendanceException::class);
});
