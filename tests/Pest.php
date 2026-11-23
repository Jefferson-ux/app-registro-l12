<?php

use App\Enums\DeviceType;
use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');

function tenantFixture(array $attributes = []): Tenant
{
    return Tenant::create(array_merge([
        'name' => 'Empresa '.Str::random(6),
        'email' => 'tenant-'.Str::uuid().'@example.com',
        'timezone' => 'America/Lima',
    ], $attributes));
}

function userFixture(Tenant $tenant, array $attributes = []): User
{
    return User::create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Usuario '.Str::random(6),
        'email' => 'user-'.Str::uuid().'@example.com',
        'password' => 'password',
        'status' => 'active',
    ], $attributes));
}

function branchFixture(Tenant $tenant, array $attributes = []): Branch
{
    return Branch::create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Sede '.Str::random(6),
    ], $attributes));
}

function employeeFixture(Tenant $tenant, array $attributes = []): Employee
{
    return Employee::create(array_merge([
        'tenant_id' => $tenant->id,
        'employee_code' => 'EMP-'.Str::upper(Str::random(8)),
        'first_name' => 'Ana',
        'last_name' => 'Perez',
        'employment_status' => 'active',
    ], $attributes));
}

function scheduleFixture(Employee $employee, bool $withBreak = true, array $attributes = []): array
{
    $today = now()->toDateString();

    $workSchedule = WorkSchedule::create([
        'tenant_id' => $employee->tenant_id,
        'name' => 'Horario '.Str::random(6),
        'schedule_type' => 'fixed',
    ]);

    $day = WorkScheduleDay::create(array_merge([
        'tenant_id' => $employee->tenant_id,
        'work_schedule_id' => $workSchedule->id,
        'day_of_week' => now()->dayOfWeekIso,
        'is_working_day' => true,
        'check_in_time' => '08:00:00',
        'check_out_time' => '17:00:00',
        'break_start_time' => $withBreak ? '13:00:00' : null,
        'break_end_time' => $withBreak ? '14:00:00' : null,
        'check_in_tolerance_minutes' => 0,
        'check_out_tolerance_minutes' => 0,
    ], $attributes));

    $assignment = EmployeeSchedule::create([
        'tenant_id' => $employee->tenant_id,
        'employee_id' => $employee->id,
        'work_schedule_id' => $workSchedule->id,
        'start_date' => $today,
        'end_date' => null,
        'status' => true,
    ]);

    return [$workSchedule, $day, $assignment];
}

function markRequestFor(User $user, array $payload): Request
{
    $request = Request::create('/api/v1/attendance/mark', 'POST', $payload);
    $request->setUserResolver(fn () => $user);

    return $request;
}

function attendanceFailure(Closure $callback): AttendanceException
{
    try {
        $callback();
    } catch (AttendanceException $exception) {
        return $exception;
    }

    throw new RuntimeException('Se esperaba una AttendanceException.');
}

function currentSessionFor(Employee $employee): ?AttendanceSession
{
    return AttendanceSession::withoutGlobalScopes()
        ->where('tenant_id', $employee->tenant_id)
        ->where('employee_id', $employee->id)
        ->whereDate('attendance_date', now()->toDateString())
        ->first();
}

function kioskDeviceFixture(Tenant $tenant, ?Branch $branch = null, array $attributes = []): array
{
    $device = new Device([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch?->id,
        'name' => 'Kiosco '.Str::random(6),
        'type' => DeviceType::Kiosk,
    ]);

    $device->forceFill($attributes);
    $device->save();

    $token = $device->issueToken();

    return [$device, $token];
}

function clearAuditLogs(): void
{
    AuditLog::withoutGlobalScopes()->delete();
}
