<?php

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use App\Services\Attendance\Policies\FixedSchedulePolicy;
use App\Services\Attendance\Policies\FlexibleSchedulePolicy;
use App\Services\Attendance\Policies\UnsupportedSchedulePolicy;
use App\Services\Attendance\SchedulePolicyResolver;
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

function assignScheduleTypeFixture(Tenant $tenant, Employee $employee, string $type): WorkSchedule
{
    $schedule = WorkSchedule::create([
        'tenant_id' => $tenant->id,
        'name' => 'Horario '.Str::random(6),
        'schedule_type' => $type,
    ]);

    EmployeeSchedule::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'work_schedule_id' => $schedule->id,
        'start_date' => now()->toDateString(),
        'end_date' => null,
        'status' => true,
    ]);

    return $schedule;
}

it('resuelve la política correcta según el tipo de horario', function (?string $type, string $expected): void {
    $resolver = app(SchedulePolicyResolver::class);

    expect($resolver->for($type))->toBeInstanceOf($expected);
})->with([
    'fixed' => ['fixed', FixedSchedulePolicy::class],
    'sin horario' => [null, FixedSchedulePolicy::class],
    'flexible' => ['flexible', FlexibleSchedulePolicy::class],
    'rotating' => ['rotating', UnsupportedSchedulePolicy::class],
    'desconocido' => ['otro', UnsupportedSchedulePolicy::class],
]);

it('permite marcar a un empleado con horario fijo en su día laborable', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FIJO']);
    scheduleFixture($employee, withBreak: false);

    expect($employee->hasWorkScheduleToday())->toBeTrue()
        ->and($employee->todayScheduleAnalysis()['blocked_reason'])->toBeNull();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    auth()->forgetGuards();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-FIJO',
            'request_id' => (string) Str::uuid(),
            'device_identifier' => 'tablet-001',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(1);
});

it('permite marcar a un jornalero flexible cualquier día y cuenta las horas', function (): void {
    Carbon::setTestNow(Carbon::create(2026, 11, 28, 8, 0, 0, 'America/Lima')); // sábado

    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-FLEX']);
    assignScheduleTypeFixture($tenant, $employee, 'flexible');

    expect($employee->hasWorkScheduleToday())->toBeTrue()
        ->and($employee->todayScheduleAnalysis()['blocked_reason'])->toBeNull();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    auth()->forgetGuards();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-FLEX',
            'type' => 'ENTRY',
            'request_id' => (string) Str::uuid(),
            'device_identifier' => 'tablet-001',
        ])
        ->assertOk();

    auth()->forgetGuards();

    Carbon::setTestNow(Carbon::create(2026, 11, 28, 12, 0, 0, 'America/Lima'));

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-FLEX',
            'type' => 'EXIT',
            'request_id' => (string) Str::uuid(),
            'device_identifier' => 'tablet-001',
        ])
        ->assertOk();

    $session = currentSessionFor($employee);

    expect($session->check_in_at)->not->toBeNull()
        ->and($session->check_out_at)->not->toBeNull()
        ->and($session->worked_minutes)->toBe(240);
});

it('bloquea la marcación de un horario rotativo con un mensaje claro', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-ROT']);
    assignScheduleTypeFixture($tenant, $employee, 'rotating');

    $analysis = $employee->todayScheduleAnalysis();

    expect($employee->hasWorkScheduleToday())->toBeFalse()
        ->and($analysis['blocked_reason'])->not->toBeNull()
        ->and($analysis['blocked_reason'])->toContain('rotativo');

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    auth()->forgetGuards();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-ROT',
            'request_id' => (string) Str::uuid(),
            'device_identifier' => 'tablet-001',
        ])
        ->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'rotativo'));

    expect(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(0);
});
