<?php

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

it('permite marcar desde un kiosco conectado con un empleado activo', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-ACTIVO']);
    scheduleFixture($employee, withBreak: false);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    auth()->forgetGuards();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-ACTIVO',
            'request_id' => (string) Str::uuid(),
            'device_identifier' => 'tablet-001',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(1);
});

it('bloquea marcaciones si el kiosco es desactivado mientras la tablet sigue abierta', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [$device, $token] = kioskDeviceFixture($tenant, $branch);

    $employee = employeeFixture($tenant, ['employee_code' => 'EMP-KIOSCO']);
    scheduleFixture($employee, withBreak: false);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    $device->refresh();
    $device->is_active = false;
    $device->save();

    expect($device->fresh()->tokens()->count())->toBe(1);

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/kiosk/info')
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    auth()->forgetGuards();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-KIOSCO',
            'request_id' => (string) Str::uuid(),
            'device_identifier' => 'tablet-001',
        ])
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    expect(AttendanceRecord::withoutGlobalScopes()->count())->toBe(0);

    $device->is_active = true;
    $device->save();

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/kiosk/info')
        ->assertOk();
});

it('rechaza el token de un kiosco desconectado desde el panel', function (): void {
    $tenant = tenantFixture();
    $branch = branchFixture($tenant);
    [$device, $token] = kioskDeviceFixture($tenant, $branch);

    employeeFixture($tenant, ['employee_code' => 'EMP-KIOSCO']);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    $device->refresh();
    $device->disconnect();

    expect($device->fresh()->tokens()->count())->toBe(0)
        ->and($device->fresh()->is_active)->toBeFalse();

    auth()->forgetGuards();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'EMP-KIOSCO',
            'request_id' => (string) Str::uuid(),
        ])
        ->assertStatus(401);

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/kiosk/info')
        ->assertStatus(401);
});
