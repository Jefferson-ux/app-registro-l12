<?php

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\WorkFlexibleRule;
use App\Models\WorkSchedule;
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

function flexibleScheduleFixture(Tenant $tenant, Employee $employee, array $ruleAttributes = []): WorkSchedule
{
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

    WorkFlexibleRule::create(array_merge([
        'tenant_id' => $tenant->id,
        'work_schedule_id' => $schedule->id,
        'max_shift_minutes' => 480,
    ], $ruleAttributes));

    return $schedule;
}

function flexibleMarkPayload(string $employeeCode, string $type): array
{
    return [
        'employee_code' => $employeeCode,
        'type' => $type,
        'request_id' => (string) Str::uuid(),
        'device_identifier' => 'tablet-001',
    ];
}

function connectFlexibleKiosk($test, string $token): void
{
    $test->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    auth()->forgetGuards();
}

function flexibleSessionFor(Employee $employee, ?int $excludeId = null): ?AttendanceSession
{
    return AttendanceSession::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
        ->first();
}

it('mantiene la misma sesión al entrar y salir cruzando medianoche', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FLEX-NOCHE']);
    flexibleScheduleFixture($tenant, $employee, ['max_shift_minutes' => 480]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 23, 0, 0, 'America/Lima'));

    connectFlexibleKiosk($this, $token);

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-NOCHE', 'ENTRY'))
        ->assertOk()
        ->assertJsonPath('success', true);

    auth()->forgetGuards();

    $session = flexibleSessionFor($employee);

    expect($session)->not->toBeNull()
        ->and($session->attendance_date->toDateString())->toBe('2026-11-23')
        ->and($session->check_out_at)->toBeNull();

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 1, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-NOCHE', 'EXIT'))
        ->assertOk();

    auth()->forgetGuards();

    $session->refresh();

    expect($session->attendance_date->toDateString())->toBe('2026-11-23')
        ->and($session->check_out_at)->not->toBeNull()
        ->and($session->worked_minutes)->toBe(120)
        ->and(AttendanceSession::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(1);
});

it('cierra como incomplete un turno flexible abandonado al volver a marcar', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FLEX-LAZY']);
    flexibleScheduleFixture($tenant, $employee, ['max_shift_minutes' => 60]);

    connectFlexibleKiosk($this, $token);

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-LAZY', 'ENTRY'))
        ->assertOk();

    auth()->forgetGuards();

    $abandoned = flexibleSessionFor($employee);

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 10, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-LAZY', 'ENTRY'))
        ->assertOk();

    auth()->forgetGuards();

    $abandoned->refresh();
    $newSession = flexibleSessionFor($employee, $abandoned->id);

    expect($abandoned->status)->toBe('incomplete')
        ->and($abandoned->check_out_at)->toBeNull()
        ->and($newSession)->not->toBeNull()
        ->and($newSession->attendance_date->toDateString())->toBe('2026-11-24')
        ->and($newSession->check_in_at)->not->toBeNull();
});

it('exige completar el descanso cuando el turno flexible lo requiere', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FLEX-BREAK']);
    flexibleScheduleFixture($tenant, $employee, [
        'requires_break' => true,
        'min_break_minutes' => 10,
        'max_break_minutes' => 60,
    ]);

    connectFlexibleKiosk($this, $token);

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-BREAK', 'ENTRY'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 9, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-BREAK', 'EXIT'))
        ->assertStatus(409);

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 9, 10, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-BREAK', 'BREAK_START'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 9, 15, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-BREAK', 'BREAK_END'))
        ->assertStatus(422);

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 9, 20, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-BREAK', 'BREAK_END'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 9, 30, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-BREAK', 'EXIT'))
        ->assertOk();

    auth()->forgetGuards();

    $session = flexibleSessionFor($employee);

    expect($session->break_start_at)->not->toBeNull()
        ->and($session->break_end_at)->not->toBeNull()
        ->and($session->check_out_at)->not->toBeNull()
        ->and($session->worked_minutes)->toBe(80);
});

it('bloquea la entrada flexible fuera de la ventana configurada', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FLEX-WINDOW']);
    flexibleScheduleFixture($tenant, $employee, [
        'entry_window_start' => '08:00:00',
        'entry_window_end' => '10:00:00',
    ]);

    connectFlexibleKiosk($this, $token);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 11, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-WINDOW', 'ENTRY'))
        ->assertStatus(422)
        ->assertJsonPath('message', 'La entrada solo se permite entre las 08:00 y las 10:00.');

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 9, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', flexibleMarkPayload('EMP-FLEX-WINDOW', 'ENTRY'))
        ->assertOk();
});

it('el cierre diario deja incomplete y nunca genera absent en horarios flexibles', function (): void {
    $tenant = tenantFixture();

    $employeeWithShift = employeeFixture($tenant, ['employee_code' => 'EMP-FLEX-ABANDON']);
    flexibleScheduleFixture($tenant, $employeeWithShift, ['max_shift_minutes' => 60]);

    $employeeWithoutMarks = employeeFixture($tenant, ['employee_code' => 'EMP-FLEX-SIN-MARCAS']);
    flexibleScheduleFixture($tenant, $employeeWithoutMarks, ['max_shift_minutes' => 60]);

    $abandoned = AttendanceSession::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employeeWithShift->id,
        'attendance_date' => '2026-11-23',
        'check_in_at' => Carbon::create(2026, 11, 23, 8, 0, 0, 'America/Lima'),
        'status' => 'present',
    ]);

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 10, 0, 0, 'America/Lima'));

    $this->artisan('attendance:close-daily', [
        '--date' => '23/11/26',
        '--tenant-id' => $tenant->id,
    ])->assertSuccessful();

    $abandoned->refresh();

    expect($abandoned->status)->toBe('incomplete')
        ->and($abandoned->check_out_at)->toBeNull()
        ->and(AttendanceSession::withoutGlobalScopes()->where('employee_id', $employeeWithShift->id)->count())->toBe(1)
        ->and(AttendanceSession::withoutGlobalScopes()->where('employee_id', $employeeWithoutMarks->id)->count())->toBe(0);
});
