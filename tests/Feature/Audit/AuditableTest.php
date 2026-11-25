<?php

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('audita la creación sin guardar campos sensibles ni timestamps', function (): void {
    $tenant = tenantFixture();
    $actor = userFixture($tenant);

    clearAuditLogs();

    $this->actingAs($actor);
    $created = userFixture($tenant);

    $log = AuditLog::withoutGlobalScopes()
        ->where('action', 'user.created')
        ->where('entity_type', 'User')
        ->where('entity_id', $created->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->tenant_id)->toBe($tenant->id)
        ->and($log->user_id)->toBe($actor->id)
        ->and($log->new_values)->toHaveKey('email')
        ->and($log->new_values)->not->toHaveKeys([
            'password',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
});

it('audita solo los atributos modificados con sus valores anteriores y nuevos', function (): void {
    $tenant = tenantFixture();
    $actor = userFixture($tenant);
    $employee = employeeFixture($tenant, [
        'first_name' => 'Ana',
        'last_name' => 'Perez',
    ]);

    clearAuditLogs();

    $this->actingAs($actor);
    $employee->update(['first_name' => 'Beatriz']);

    $log = AuditLog::withoutGlobalScopes()
        ->where('action', 'employee.updated')
        ->where('entity_type', 'Employee')
        ->where('entity_id', $employee->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->old_values)->toBe(['first_name' => 'Ana'])
        ->and($log->new_values)->toBe(['first_name' => 'Beatriz'])
        ->and($log->user_id)->toBe($actor->id)
        ->and($log->user_name)->toBe($actor->name);
});

it('audita el borrado lógico conservando los valores anteriores', function (): void {
    $tenant = tenantFixture();
    $actor = userFixture($tenant);
    $employee = employeeFixture($tenant, [
        'first_name' => 'Ana',
        'last_name' => 'Perez',
    ]);

    clearAuditLogs();

    $this->actingAs($actor);
    $employee->delete();

    $log = AuditLog::withoutGlobalScopes()
        ->where('action', 'employee.soft_delete')
        ->where('entity_type', 'Employee')
        ->where('entity_id', $employee->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values)->toBeNull()
        ->and($log->old_values)->toHaveKey('first_name')
        ->and($log->old_values['first_name'])->toBe('Ana')
        ->and($log->old_values)->not->toHaveKeys(['deleted_at', 'updated_at', 'created_at']);
});

it('no audita una actualización que solo modifica campos excluidos', function (): void {
    $tenant = tenantFixture();
    $actor = userFixture($tenant);
    $employee = employeeFixture($tenant);

    clearAuditLogs();

    $this->actingAs($actor);
    $employee->update(['updated_at' => now()->addMinute()]);

    expect(AuditLog::withoutGlobalScopes()
        ->where('action', 'employee.updated')
        ->where('entity_id', $employee->id)
        ->exists())->toBeFalse();
});

it('aísla los registros de auditoría por empresa', function (): void {
    $tenantA = tenantFixture();
    $userA = userFixture($tenantA);
    $employeeA = employeeFixture($tenantA);

    $tenantB = tenantFixture();
    $employeeB = employeeFixture($tenantB);

    $this->actingAs($userA);

    expect(AuditLog::where('entity_type', 'Employee')->where('entity_id', $employeeA->id)->exists())->toBeTrue()
        ->and(AuditLog::where('entity_type', 'Employee')->where('entity_id', $employeeB->id)->exists())->toBeFalse()
        ->and(AuditLog::where('tenant_id', $tenantB->id)->exists())->toBeFalse();
});
