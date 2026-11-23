<?php

use App\Actions\Attendance\MarkAttendanceAction;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Employee;
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

it('no encuentra un empleado que solo existe en otra empresa', function (): void {
    $tenantA = tenantFixture();
    $userA = userFixture($tenantA);
    $tenantB = tenantFixture();

    employeeFixture($tenantB, ['employee_code' => 'EMP-OTRA-EMPRESA']);

    $this->actingAs($userA);

    $exception = attendanceFailure(fn () => app(MarkAttendanceAction::class)->execute(
        markRequestFor($userA, [
            'employee_code' => 'EMP-OTRA-EMPRESA',
            'request_id' => (string) Str::uuid(),
        ])
    ));

    expect($exception->status)->toBe(404)
        ->and($exception->getMessage())->toContain('no existe');
});

it('marca únicamente al empleado de la empresa autenticada aunque compartan código', function (): void {
    $tenantA = tenantFixture();
    $userA = userFixture($tenantA);
    $employeeA = employeeFixture($tenantA, ['employee_code' => 'EMP-001']);
    scheduleFixture($employeeA, withBreak: false);

    $tenantB = tenantFixture();
    $userB = userFixture($tenantB);
    $employeeB = employeeFixture($tenantB, ['employee_code' => 'EMP-001']);

    $this->actingAs($userA);

    app(MarkAttendanceAction::class)->execute(markRequestFor($userA, [
        'employee_code' => 'EMP-001',
        'request_id' => (string) Str::uuid(),
    ]));

    expect(currentSessionFor($employeeA))->not->toBeNull()
        ->and(currentSessionFor($employeeB))->toBeNull()
        ->and(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employeeA->id)->count())->toBe(1)
        ->and(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employeeB->id)->count())->toBe(0);

    $this->actingAs($userB);

    app(MarkAttendanceAction::class)->execute(markRequestFor($userB, [
        'employee_code' => 'EMP-001',
        'request_id' => (string) Str::uuid(),
    ]));

    expect(currentSessionFor($employeeA)->check_out_at)->toBeNull()
        ->and(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employeeA->id)->count())->toBe(1)
        ->and(AttendanceRecord::withoutGlobalScopes()->where('employee_id', $employeeB->id)->count())->toBe(1);
});

it('aplica el scope global de tenant a consultas y creaciones', function (): void {
    $tenantA = tenantFixture();
    $userA = userFixture($tenantA);
    $employeeA = employeeFixture($tenantA);

    $tenantB = tenantFixture();
    $employeeB = employeeFixture($tenantB, ['employee_code' => 'EMP-SOLO-B']);

    $this->actingAs($userA);

    expect(Employee::where('employee_code', 'EMP-SOLO-B')->first())->toBeNull();

    $session = AttendanceSession::create([
        'employee_id' => $employeeA->id,
        'attendance_date' => now()->toDateString(),
    ]);

    expect($session->tenant_id)->toBe($tenantA->id)
        ->and(AttendanceSession::withoutGlobalScopes()->where('tenant_id', $tenantB->id)->exists())->toBeFalse()
        ->and(AttendanceSession::withoutGlobalScopes()->where('employee_id', $employeeB->id)->exists())->toBeFalse();
});
