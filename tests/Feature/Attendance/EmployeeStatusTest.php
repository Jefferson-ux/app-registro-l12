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

it('bloquea la marcación de un empleado con estado :status', function (string $status): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant, [
        'employee_code' => 'EMP-ESTADO',
        'employment_status' => $status,
    ]);
    scheduleFixture($employee, withBreak: true);

    $this->actingAs($user);

    $exception = attendanceFailure(fn () => app(MarkAttendanceAction::class)->execute(
        markRequestFor($user, [
            'employee_code' => 'EMP-ESTADO',
            'request_id' => (string) Str::uuid(),
        ])
    ));

    expect($exception->status)->toBe(403)
        ->and($exception->getMessage())->toContain('activo')
        ->and(currentSessionFor($employee))->toBeNull()
        ->and(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(0);
})->with(['inactive', 'suspended', 'terminated']);

it('bloquea la siguiente marcación si el empleado es cesado a mitad de jornada', function (): void {
    $tenant = tenantFixture();
    $user = userFixture($tenant);
    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-CESADO']);
    scheduleFixture($employee, withBreak: false);

    $this->actingAs($user);
    $action = app(MarkAttendanceAction::class);
    $payload = ['employee_code' => 'EMP-CESADO', 'request_id' => (string) Str::uuid()];

    $action->execute(markRequestFor($user, $payload));

    expect(currentSessionFor($employee)->check_in_at)->not->toBeNull();

    $employee->update([
        'employment_status' => 'terminated',
        'termination_date' => now()->toDateString(),
    ]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 12, 0, 0, 'America/Lima'));

    $exception = attendanceFailure(fn () => $action->execute(markRequestFor($user, $payload)));

    expect($exception->status)->toBe(403)
        ->and(currentSessionFor($employee)->check_out_at)->toBeNull()
        ->and(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(1);
});

it('rechaza por kiosco la marcación de un empleado cesado', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    employeeFixture($tenant, [
        'employee_code' => 'EMP-CESADO-KIOSCO',
        'employment_status' => 'terminated',
    ]);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    auth()->forgetGuards();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-CESADO-KIOSCO',
            'request_id' => (string) Str::uuid(),
            'device_identifier' => 'tablet-001',
        ])
        ->assertStatus(403)
        ->assertJsonPath('success', false);

    expect(AttendanceRecord::withoutGlobalScopes()->count())->toBe(0);
});
