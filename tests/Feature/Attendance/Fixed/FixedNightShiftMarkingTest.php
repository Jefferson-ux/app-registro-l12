<?php

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

function fixedNightScheduleFixture(
    Tenant $tenant,
    Employee $employee,
    int $dayOfWeek,
    array $dayAttributes = [],
): void {
    $schedule = WorkSchedule::create([
        'tenant_id' => $tenant->id,
        'name' => 'Fijo Nocturno '.Str::random(6),
        'schedule_type' => 'fixed',
    ]);

    WorkScheduleDay::create(array_merge([
        'tenant_id' => $tenant->id,
        'work_schedule_id' => $schedule->id,
        'day_of_week' => $dayOfWeek,
        'is_working_day' => true,
        'check_in_time' => '20:00:00',
        'check_out_time' => '04:00:00',
        'break_start_time' => null,
        'break_end_time' => null,
        'check_in_tolerance_minutes' => 0,
        'check_out_tolerance_minutes' => 0,
        'auto_close_after_minutes' => 60,
    ], $dayAttributes));

    EmployeeSchedule::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'work_schedule_id' => $schedule->id,
        'start_date' => '2026-11-01',
        'end_date' => null,
        'status' => true,
    ]);
}

function fixedNightMarkPayload(string $employeeCode, string $type): array
{
    return [
        'employee_code' => $employeeCode,
        'type' => $type,
        'request_id' => (string) Str::uuid(),
        'device_identifier' => 'tablet-001',
    ];
}

function connectFixedNightKiosk($test, string $token): void
{
    $test->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    auth()->forgetGuards();
}

it('completa un turno fijo nocturno manteniendo la sesión del día de inicio', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FIX-NOCHE']);
    fixedNightScheduleFixture($tenant, $employee, dayOfWeek: 1, dayAttributes: [
        'break_start_time' => '00:00:00',
        'break_end_time' => '00:30:00',
    ]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 20, 0, 0, 'America/Lima'));

    connectFixedNightKiosk($this, $token);

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-NOCHE', 'ENTRY'))
        ->assertOk()
        ->assertJsonPath('success', true);

    auth()->forgetGuards();

    $session = AttendanceSession::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->first();

    expect($session)->not->toBeNull()
        ->and($session->attendance_date->toDateString())->toBe('2026-11-23')
        ->and($session->scheduled_minutes)->toBe(450);

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 0, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-NOCHE', 'BREAK_START'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 0, 30, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-NOCHE', 'BREAK_END'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 4, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-NOCHE', 'EXIT'))
        ->assertOk();

    auth()->forgetGuards();

    $session->refresh();

    expect($session->attendance_date->toDateString())->toBe('2026-11-23')
        ->and($session->check_out_at)->not->toBeNull()
        ->and($session->worked_minutes)->toBe(450)
        ->and(AttendanceSession::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(1);
});

it('bloquea la salida anticipada antes de medianoche en un turno nocturno', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FIX-NOCHE-TEMPRANO']);
    fixedNightScheduleFixture($tenant, $employee, dayOfWeek: 1);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 20, 0, 0, 'America/Lima'));

    connectFixedNightKiosk($this, $token);

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-NOCHE-TEMPRANO', 'ENTRY'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 23, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-NOCHE-TEMPRANO', 'EXIT'))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 3, 30, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-NOCHE-TEMPRANO', 'EXIT'))
        ->assertOk();

    auth()->forgetGuards();

    $session = AttendanceSession::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->first();

    expect($session->check_out_at)->not->toBeNull()
        ->and($session->worked_minutes)->toBe(450)
        ->and($session->early_leave_minutes)->toBe(30)
        ->and($session->overtime_minutes)->toBe(0);
});

it('permite salir después de medianoche aunque el día siguiente no sea laborable', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FIX-SABADO']);
    fixedNightScheduleFixture($tenant, $employee, dayOfWeek: 6);

    Carbon::setTestNow(Carbon::create(2026, 11, 28, 20, 0, 0, 'America/Lima'));

    connectFixedNightKiosk($this, $token);

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-SABADO', 'ENTRY'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 29, 4, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-SABADO', 'EXIT'))
        ->assertOk();

    auth()->forgetGuards();

    $session = AttendanceSession::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->first();

    expect($session->attendance_date->toDateString())->toBe('2026-11-28')
        ->and($session->check_out_at)->not->toBeNull()
        ->and($session->worked_minutes)->toBe(480);
});

it('cierra automáticamente una sesión fija si se marca después del auto cierre', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FIX-AUTOCIERRE']);
    fixedNightScheduleFixture($tenant, $employee, dayOfWeek: 1, dayAttributes: [
        'check_in_time' => '08:00:00',
        'check_out_time' => '17:00:00',
        'auto_close_after_minutes' => 10,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 8, 0, 0, 'America/Lima'));

    connectFixedNightKiosk($this, $token);

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-AUTOCIERRE', 'ENTRY'))
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 18, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', fixedNightMarkPayload('EMP-FIX-AUTOCIERRE', 'EXIT'))
        ->assertStatus(409);

    auth()->forgetGuards();

    $session = AttendanceSession::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->first();

    expect($session->status)->toBe('incomplete')
        ->and($session->check_out_at)->not->toBeNull()
        ->and($session->check_out_at->format('H:i'))->toBe('17:00');
});
