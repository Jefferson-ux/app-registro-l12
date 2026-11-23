<?php

use App\Actions\Attendance\MarkAttendanceAction;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 11, 23, 8, 0, 0, 'America/Lima'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function markAt(string $time): void
{
    [$hour, $minute] = array_map('intval', explode(':', $time));
    Carbon::setTestNow(Carbon::create(2026, 11, 23, $hour, $minute, 0, 'America/Lima'));
}

it('encadena entrada, inicio de descanso, fin de descanso y salida', function (): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: true);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);
    $payload = ['employee_code' => $employee->employee_code, 'request_id' => (string) Str::uuid()];

    $action->execute(markRequestFor($user, $payload));
    expect(currentSessionFor($employee)->check_in_at)->not->toBeNull();

    markAt('13:05');
    $breakStart = $action->execute(markRequestFor($user, $payload));
    expect($breakStart['session']->break_start_at)->not->toBeNull();

    markAt('13:45');
    $breakEnd = $action->execute(markRequestFor($user, $payload));
    expect($breakEnd['session']->break_end_at)->not->toBeNull();

    markAt('17:00');
    $exit = $action->execute(markRequestFor($user, $payload));
    expect($exit['session']->check_out_at)->not->toBeNull()
        ->and($exit['session']->worked_minutes)->toBe(500);

    $types = AttendanceRecord::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->orderBy('id')
        ->pluck('type')
        ->all();

    expect($types)->toBe(['check_in', 'break_start', 'break_end', 'check_out']);
});

it('salta de entrada a salida cuando el horario no tiene descanso', function (): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: false);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);
    $payload = ['employee_code' => $employee->employee_code, 'request_id' => (string) Str::uuid()];

    $action->execute(markRequestFor($user, $payload));

    markAt('17:00');
    $exit = $action->execute(markRequestFor($user, $payload));

    expect($exit['session']->break_start_at)->toBeNull()
        ->and($exit['session']->break_end_at)->toBeNull()
        ->and($exit['session']->check_out_at)->not->toBeNull();

    $types = AttendanceRecord::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->orderBy('id')
        ->pluck('type')
        ->all();

    expect($types)->toBe(['check_in', 'check_out']);
});

it('respeta el tipo explícito enviado por el kiosco', function (): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: true);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);

    $action->execute(markRequestFor($user, [
        'employee_code' => $employee->employee_code,
        'type' => 'ENTRY',
        'request_id' => (string) Str::uuid(),
    ]));

    markAt('16:00');

    $result = $action->execute(markRequestFor($user, [
        'employee_code' => $employee->employee_code,
        'type' => 'EXIT',
        'request_id' => (string) Str::uuid(),
    ]));

    expect($result['session']->check_out_at)->not->toBeNull()
        ->and($result['session']->break_start_at)->toBeNull();
});

it('rechaza una segunda entrada el mismo día', function (): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: true);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);

    $action->execute(markRequestFor($user, [
        'employee_code' => $employee->employee_code,
        'type' => 'ENTRY',
        'request_id' => (string) Str::uuid(),
    ]));

    $exception = attendanceFailure(fn () => $action->execute(markRequestFor($user, [
        'employee_code' => $employee->employee_code,
        'type' => 'ENTRY',
        'request_id' => (string) Str::uuid(),
    ])));

    expect($exception->status)->toBe(409)
        ->and($exception->getMessage())->toContain('ENTRADA');
});

it('rechaza la salida y el descanso antes de registrar la entrada', function (string $type): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: true);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);

    $exception = attendanceFailure(fn () => $action->execute(markRequestFor($user, [
        'employee_code' => $employee->employee_code,
        'type' => $type,
        'request_id' => (string) Str::uuid(),
    ])));

    expect($exception->status)->toBe(422);
})->with(['EXIT', 'BREAK_START', 'BREAK_END']);

it('rechaza el fin de descanso cuando no se inició', function (): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: true);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);

    $action->execute(markRequestFor($user, [
        'employee_code' => $employee->employee_code,
        'type' => 'ENTRY',
        'request_id' => (string) Str::uuid(),
    ]));

    $exception = attendanceFailure(fn () => $action->execute(markRequestFor($user, [
        'employee_code' => $employee->employee_code,
        'type' => 'BREAK_END',
        'request_id' => (string) Str::uuid(),
    ])));

    expect($exception->status)->toBe(422)
        ->and($exception->getMessage())->toContain('Break');
});

it('bloquea nuevas marcaciones cuando la jornada ya está completada', function (): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: true);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);
    $payload = ['employee_code' => $employee->employee_code, 'request_id' => (string) Str::uuid()];

    $action->execute(markRequestFor($user, $payload));
    markAt('13:05');
    $action->execute(markRequestFor($user, $payload));
    markAt('13:45');
    $action->execute(markRequestFor($user, $payload));
    markAt('17:00');
    $action->execute(markRequestFor($user, $payload));

    $before = AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id)->count();

    $exception = attendanceFailure(fn () => $action->execute(markRequestFor($user, $payload)));

    expect($exception->status)->toBe(409)
        ->and($exception->getMessage())->toContain('completada')
        ->and(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe($before);
});
