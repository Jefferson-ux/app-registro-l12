<?php

use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 11, 23, 8, 0, 0, 'America/Lima'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function closeDailyFixture(array $dayAttributes = []): array
{
    $tenant = tenantFixture();
    $employee = employeeFixture($tenant);
    scheduleFixture($employee, withBreak: false, attributes: $dayAttributes);

    return [$tenant, $employee];
}

it('cierra como incomplete una sesión fija al cumplirse el auto cierre', function (): void {
    [$tenant, $employee] = closeDailyFixture(['auto_close_after_minutes' => 30]);

    $session = AttendanceSession::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-23',
        'check_in_at' => Carbon::create(2026, 11, 23, 8, 0, 0, 'America/Lima'),
        'status' => 'present',
    ]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 17, 31, 0, 'America/Lima'));

    $this->artisan('attendance:close-daily', [
        '--date' => '23/11/26',
        '--tenant-id' => $tenant->id,
    ])->assertSuccessful();

    $session->refresh();

    expect($session->status)->toBe('incomplete')
        ->and($session->check_out_at)->not->toBeNull()
        ->and($session->check_out_at->format('H:i'))->toBe('17:00');
});

it('no cierra una sesión fija antes del auto cierre', function (): void {
    [$tenant, $employee] = closeDailyFixture(['auto_close_after_minutes' => 30]);

    $session = AttendanceSession::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-23',
        'check_in_at' => Carbon::create(2026, 11, 23, 8, 0, 0, 'America/Lima'),
        'status' => 'present',
    ]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 17, 29, 0, 'America/Lima'));

    $this->artisan('attendance:close-daily', [
        '--date' => '23/11/26',
        '--tenant-id' => $tenant->id,
    ])->assertSuccessful();

    $session->refresh();

    expect($session->status)->toBe('present')
        ->and($session->check_out_at)->toBeNull();
});

it('crea absent en un día laborable sin entrada solo después del auto cierre', function (): void {
    [$tenant, $employee] = closeDailyFixture(['auto_close_after_minutes' => 30]);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 17, 29, 0, 'America/Lima'));

    $this->artisan('attendance:close-daily', [
        '--date' => '23/11/26',
        '--tenant-id' => $tenant->id,
    ])->assertSuccessful();

    expect(AttendanceSession::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(0);

    Carbon::setTestNow(Carbon::create(2026, 11, 23, 17, 31, 0, 'America/Lima'));

    $this->artisan('attendance:close-daily', [
        '--date' => '23/11/26',
        '--tenant-id' => $tenant->id,
    ])->assertSuccessful();

    $sessions = AttendanceSession::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->get();

    expect($sessions)->toHaveCount(1)
        ->and($sessions->first()->status)->toBe('absent')
        ->and($sessions->first()->check_in_at)->toBeNull()
        ->and($sessions->first()->scheduled_minutes)->toBe(540);
});

it('respeta turnos fijos que cruzan medianoche al auto cerrar', function (): void {
    [$tenant, $employee] = closeDailyFixture([
        'check_in_time' => '22:00:00',
        'check_out_time' => '06:00:00',
        'auto_close_after_minutes' => 30,
    ]);

    $session = AttendanceSession::create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-23',
        'check_in_at' => Carbon::create(2026, 11, 23, 22, 0, 0, 'America/Lima'),
        'status' => 'present',
    ]);

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 6, 0, 0, 'America/Lima'));

    $this->artisan('attendance:close-daily', [
        '--date' => '23/11/26',
        '--tenant-id' => $tenant->id,
    ])->assertSuccessful();

    $session->refresh();

    expect($session->status)->toBe('present')
        ->and($session->check_out_at)->toBeNull();

    Carbon::setTestNow(Carbon::create(2026, 11, 24, 6, 31, 0, 'America/Lima'));

    $this->artisan('attendance:close-daily', [
        '--date' => '23/11/26',
        '--tenant-id' => $tenant->id,
    ])->assertSuccessful();

    $session->refresh();

    expect($session->status)->toBe('incomplete')
        ->and($session->check_out_at->format('Y-m-d H:i'))->toBe('2026-11-24 06:00');
});
