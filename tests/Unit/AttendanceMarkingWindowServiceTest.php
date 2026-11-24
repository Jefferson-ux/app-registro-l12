<?php

use App\Enums\AttendanceType;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\WorkScheduleDay;
use App\Services\AttendanceMarkingWindowService;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

function validar(
    AttendanceType $tipo,
    WorkScheduleDay $horario,
    ?AttendanceSession $sesion,
    string $fecha,
): void {
    (new AttendanceMarkingWindowService)->validateTiming(
        $tipo,
        $horario,
        $sesion,
        Carbon::parse($fecha, 'America/Lima'),
        'America/Lima',
    );
}

it('permite marcar la entrada 60 minutos antes de su horario', function () {
    expect(fn() => validar(
        AttendanceType::Entry,
        new WorkScheduleDay(['check_in_time' => '08:00:00']),
        null,
        '2026-11-18 07:00:00',
    ))->not->toThrow(AttendanceException::class);
});

it('rechaza la entrada antes de la ventana de 60 minutos', function () {
    expect(fn() => validar(
        AttendanceType::Entry,
        new WorkScheduleDay(['check_in_time' => '08:00:00']),
        null,
        '2026-11-18 06:59:59',
    ))->toThrow(AttendanceException::class);
});

it('rechaza la salida antes de la ventana de 60 minutos', function () {
    expect(fn() => validar(
        AttendanceType::Exit,
        new WorkScheduleDay(['check_out_time' => '17:00:00']),
        null,
        '2026-11-18 15:59:59',
    ))->toThrow(AttendanceException::class);
});

it('permite marcar la salida 60 minutos antes de su horario', function () {
    expect(fn() => validar(
        AttendanceType::Exit,
        new WorkScheduleDay(['check_out_time' => '17:00:00']),
        null,
        '2026-11-18 16:00:00',
    ))->not->toThrow(AttendanceException::class);
});

it('permite el inicio de refrigerio desde 30 minutos antes y lo rechaza antes de su ventana', function () {
    $horario = new WorkScheduleDay([
        'break_start_time' => '12:00:00',
        'break_end_time' => '13:00:00',
    ]);

    expect(fn() => validar(AttendanceType::BreakStart, $horario, null, '2026-11-18 11:30:00'))
        ->not->toThrow(AttendanceException::class);

    expect(fn() => validar(AttendanceType::BreakStart, $horario, null, '2026-11-18 11:29:59'))
        ->toThrow(AttendanceException::class);
});

it('rechaza el inicio de refrigerio después del límite de marcación', function () {
    expect(fn() => validar(
        AttendanceType::BreakStart,
        new WorkScheduleDay([
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
        ]),
        null,
        '2026-11-18 23:00:01',
    ))->toThrow(AttendanceException::class);
});

it('exige la duración mínima configurada para terminar el refrigerio', function () {
    expect(fn() => validar(
        AttendanceType::BreakEnd,
        new WorkScheduleDay,
        new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']),
        '2026-11-18 12:09:59',
    ))->toThrow(AttendanceException::class);
});

it('permite terminar el refrigerio después de la duración mínima', function () {
    expect(fn() => validar(
        AttendanceType::BreakEnd,
        new WorkScheduleDay,
        new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']),
        '2026-11-18 12:10:00',
    ))->not->toThrow(AttendanceException::class);
});

it('permite terminar el refrigerio después de su hora de fin programada', function () {
    expect(fn() => validar(
        AttendanceType::BreakEnd,
        new WorkScheduleDay(['break_end_time' => '13:00:00']),
        new AttendanceSession(['break_start_at' => '2026-11-18 12:00:00']),
        '2026-11-18 13:10:00',
    ))->not->toThrow(AttendanceException::class);
});

it('rechaza cualquier marcación después de las 11 p. m.', function () {
    expect(fn() => validar(
        AttendanceType::Entry,
        new WorkScheduleDay(['check_in_time' => '08:00:00']),
        null,
        '2026-11-18 23:00:01',
    ))->toThrow(AttendanceException::class);
});

it('obliga a esperar 10 minutos después del fin del refrigerio para marcar la salida', function () {
    expect(fn() => validar(
        AttendanceType::Exit,
        new WorkScheduleDay(['check_out_time' => '17:00:00']),
        new AttendanceSession(['break_end_at' => '2026-11-18 16:00:00']),
        '2026-11-18 16:09:59',
    ))->toThrow(AttendanceException::class);
});

it('permite la salida 10 minutos después del fin del refrigerio', function () {
    expect(fn() => validar(
        AttendanceType::Exit,
        new WorkScheduleDay(['check_out_time' => '17:00:00']),
        new AttendanceSession(['break_end_at' => '2026-11-18 16:00:00']),
        '2026-11-18 16:10:00',
    ))->not->toThrow(AttendanceException::class);
});

it('obliga a esperar 10 minutos después de la entrada para iniciar el refrigerio', function () {
    expect(fn() => validar(
        AttendanceType::BreakStart,
        new WorkScheduleDay(['break_start_time' => '12:00:00']),
        new AttendanceSession(['check_in_at' => '2026-11-18 11:30:00']),
        '2026-11-18 11:39:59',
    ))->toThrow(AttendanceException::class);
});

it('permite iniciar el refrigerio 10 minutos después de la entrada', function () {
    expect(fn() => validar(
        AttendanceType::BreakStart,
        new WorkScheduleDay(['break_start_time' => '12:00:00']),
        new AttendanceSession(['check_in_at' => '2026-11-18 11:30:00']),
        '2026-11-18 11:40:00',
    ))->not->toThrow(AttendanceException::class);
});

it('obliga a esperar 10 minutos después de la entrada para marcar la salida cuando no hay refrigerio programado', function () {
    expect(fn() => validar(
        AttendanceType::Exit,
        new WorkScheduleDay(['check_out_time' => '17:00:00']),
        new AttendanceSession(['check_in_at' => '2026-11-18 16:00:00']),
        '2026-11-18 16:09:59',
    ))->toThrow(AttendanceException::class);
});
