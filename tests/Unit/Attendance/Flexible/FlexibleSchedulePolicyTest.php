<?php

use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\WorkFlexibleRule;
use App\Models\WorkSchedule;
use App\Services\Attendance\Policies\FlexibleSchedulePolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function flexiblePolicyFixture(array $ruleAttributes = []): array
{
    $tenant = tenantFixture();
    $employee = employeeFixture($tenant);

    $schedule = WorkSchedule::create([
        'tenant_id' => $tenant->id,
        'name' => 'Flexible '.Str::random(6),
        'schedule_type' => 'flexible',
    ]);

    EmployeeSchedule::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'work_schedule_id' => $schedule->id,
        'start_date' => '2026-11-01',
        'end_date' => null,
        'status' => true,
    ]);

    $rule = WorkFlexibleRule::create(array_merge([
        'tenant_id' => $tenant->id,
        'work_schedule_id' => $schedule->id,
        'max_shift_minutes' => 480,
    ], $ruleAttributes));

    return [$employee, $schedule, $rule, $tenant];
}

it('no define cierre si la sesión no tiene entrada', function (): void {
    [$employee] = flexiblePolicyFixture();

    expect((new FlexibleSchedulePolicy)->closingDeadline($employee, Carbon::now(), new AttendanceSession))
        ->toBeNull();
});

it('cierra la jornada al cumplirse max_shift_minutes desde la entrada', function (): void {
    [$employee] = flexiblePolicyFixture(['max_shift_minutes' => 300]);

    $checkIn = Carbon::parse('2026-11-23 08:00:00', 'America/Lima');
    $session = new AttendanceSession(['check_in_at' => $checkIn]);

    $deadline = (new FlexibleSchedulePolicy)->closingDeadline($employee, Carbon::now(), $session);

    expect($deadline->toDateTimeString())
        ->toBe($checkIn->copy()->addMinutes(300)->toDateTimeString());
});

it('nunca marca inasistencias', function (): void {
    expect((new FlexibleSchedulePolicy)->marksAbsences())->toBeFalse();
});

it('permite entrar cuando no hay ventana configurada', function (): void {
    [$employee] = flexiblePolicyFixture();

    expect(fn () => (new FlexibleSchedulePolicy)->validateEntry(
        $employee,
        Carbon::parse('2026-11-23 03:00:00', 'America/Lima'),
    ))->not->toThrow(AttendanceException::class);
});

it('rechaza la entrada fuera de la ventana configurada', function (): void {
    [$employee] = flexiblePolicyFixture([
        'entry_window_start' => '08:00:00',
        'entry_window_end' => '10:00:00',
    ]);

    expect(fn () => (new FlexibleSchedulePolicy)->validateEntry(
        $employee,
        Carbon::parse('2026-11-23 11:00:00', 'America/Lima'),
    ))->toThrow(AttendanceException::class, 'La entrada solo se permite entre las 08:00 y las 10:00.');
});

it('admite la entrada en los bordes de la ventana', function (): void {
    [$employee] = flexiblePolicyFixture([
        'entry_window_start' => '08:00:00',
        'entry_window_end' => '10:00:00',
    ]);

    $policy = new FlexibleSchedulePolicy;

    expect(fn () => $policy->validateEntry($employee, Carbon::parse('2026-11-23 08:00:00', 'America/Lima')))
        ->not->toThrow(AttendanceException::class);

    expect(fn () => $policy->validateEntry($employee, Carbon::parse('2026-11-23 10:00:00', 'America/Lima')))
        ->not->toThrow(AttendanceException::class);
});

it('admite ventanas de entrada que cruzan medianoche', function (): void {
    [$employee] = flexiblePolicyFixture([
        'entry_window_start' => '22:00:00',
        'entry_window_end' => '02:00:00',
    ]);

    $policy = new FlexibleSchedulePolicy;

    expect(fn () => $policy->validateEntry($employee, Carbon::parse('2026-11-23 23:30:00', 'America/Lima')))
        ->not->toThrow(AttendanceException::class);

    expect(fn () => $policy->validateEntry($employee, Carbon::parse('2026-11-23 01:30:00', 'America/Lima')))
        ->not->toThrow(AttendanceException::class);

    expect(fn () => $policy->validateEntry($employee, Carbon::parse('2026-11-23 12:00:00', 'America/Lima')))
        ->toThrow(AttendanceException::class);
});

it('exige el descanso mínimo entre turnos', function (): void {
    [$employee] = flexiblePolicyFixture(['min_rest_between_shifts_minutes' => 60]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 6, 30, 0, 'America/Lima'));

    AttendanceSession::create([
        'tenant_id' => $employee->tenant_id,
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-23',
        'check_in_at' => now()->subMinutes(90),
        'check_out_at' => now()->subMinutes(30),
    ]);

    $policy = new FlexibleSchedulePolicy;

    expect(fn () => $policy->validateEntry($employee, now()))
        ->toThrow(AttendanceException::class, 'Debe descansar al menos 60 minutos entre turnos.');

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 7, 0, 0, 'America/Lima'));

    expect(fn () => $policy->validateEntry($employee, now()))
        ->not->toThrow(AttendanceException::class);

    Carbon::setTestNow();
});

it('calcula la siguiente acción según el avance del turno', function (): void {
    [$employee] = flexiblePolicyFixture(['requires_break' => true]);

    $policy = new FlexibleSchedulePolicy;

    expect($policy->expectedNextAction($employee, null))->toBe('ENTRY');

    $checkIn = new AttendanceSession(['check_in_at' => now()]);
    expect($policy->expectedNextAction($employee, $checkIn))->toBe('BREAK_START');

    $breakStart = new AttendanceSession(['check_in_at' => now(), 'break_start_at' => now()]);
    expect($policy->expectedNextAction($employee, $breakStart))->toBe('BREAK_END');

    $breakEnd = new AttendanceSession([
        'check_in_at' => now(),
        'break_start_at' => now(),
        'break_end_at' => now(),
    ]);
    expect($policy->expectedNextAction($employee, $breakEnd))->toBe('EXIT');

    $completed = new AttendanceSession(['check_in_at' => now(), 'check_out_at' => now()]);
    expect($policy->expectedNextAction($employee, $completed))->toBe('COMPLETED');
});

it('permite salir directo cuando el turno no requiere descanso', function (): void {
    [$employee] = flexiblePolicyFixture(['requires_break' => false]);

    $session = new AttendanceSession(['check_in_at' => now()]);

    expect((new FlexibleSchedulePolicy)->expectedNextAction($employee, $session))->toBe('EXIT');
});
