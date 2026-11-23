<?php

use App\Enums\DeviceType;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createKioskFixture(): array
{
    $tenant = Tenant::create([
        'name' => 'Empresa Prueba',
        'email' => fake()->unique()->safeEmail(),
    ]);

    $branch = Branch::create([
        'tenant_id' => $tenant->id,
        'name' => 'Sede Principal',
    ]);

    $device = Device::create([
        'tenant_id' => $tenant->id,
        'branch_id' => $branch->id,
        'name' => 'Kiosco Principal',
        'type' => DeviceType::Kiosk,
    ]);

    return [$tenant, $branch, $device];
}

it('conecta un quiosco y almacena su identificador y sus coordenadas', function () {
    [,, $device] = createKioskFixture();
    $token = $device->issueToken();

    $response = $this->withToken($token)->postJson('/api/v1/kiosk/connect', [
        'device_identifier' => 'tablet-001',
        'latitude' => -12.046374,
        'longitude' => -77.042793,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Kiosco Principal')
        ->assertJsonPath('data.is_active', true);

    expect($device->fresh())
        ->is_active->toBeTrue()
        ->device_identifier->toBe('tablet-001')
        ->latitude->toBe('-12.0463740')
        ->longitude->toBe('-77.0427930');
});

it('retorna 401 si faltan los tokens de quiosco o si estos no son válidos', function () {
    $this->post('/api/v1/kiosk/connect', [
        'device_identifier' => 'tablet-001',
    ], ['Accept' => 'text/html'])
        ->assertStatus(401)
        ->assertJson(['success' => false]);

    $this->withToken('kiosk_invalido')
        ->post('/api/v1/kiosk/connect', [
            'device_identifier' => 'tablet-001',
        ], ['Accept' => 'text/html'])
        ->assertStatus(401)
        ->assertJson(['success' => false]);
});

it('rechaza tokens de usuario en rutas de quiosco y tokens de dispositivo en rutas de usuario', function () {
    [,, $device] = createKioskFixture();
    $kioskToken = $device->issueToken();

    $tenant = $device->tenant;
    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Usuario',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);
    $userToken = $user->createToken('test')->plainTextToken;

    $this->withToken($userToken)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertStatus(403);

    auth()->forgetGuards();

    $this->withToken($kioskToken)
        ->getJson('/api/v1/kiosk/employees/EMP-001/status')
        ->assertStatus(403);
});

it('valida los datos de conexión del quiosco', function () {
    [,, $device] = createKioskFixture();
    $token = $device->issueToken();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', [])
        ->assertStatus(422);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', [
            'device_identifier' => 'tablet-001',
            'latitude' => 91,
        ])
        ->assertStatus(422);
});

it('permite la reconexión únicamente para el mismo identificador de dispositivo', function () {
    [,, $device] = createKioskFixture();
    $token = $device->issueToken();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-002'])
        ->assertStatus(409);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();
});

it('informa sobre los datos del quiosco y actualiza la señal de actividad (heartbeat)', function () {
    [,, $device] = createKioskFixture();
    $token = $device->issueToken();

    $this->withToken($token)
        ->getJson('/api/v1/kiosk/info')
        ->assertStatus(409);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    $device->refresh();
    $previousHeartbeat = $device->last_used_at;

    $this->withToken($token)
        ->getJson('/api/v1/kiosk/info')
        ->assertOk()
        ->assertJsonPath('data.is_active', true);

    expect($device->fresh()->last_used_at->gte($previousHeartbeat))->toBeTrue();
});

it('desconecta de forma idempotente y revoca el token', function () {
    [,, $device] = createKioskFixture();
    $token = $device->issueToken();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', ['device_identifier' => 'tablet-001'])
        ->assertOk();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/disconnect')
        ->assertOk();

    expect($device->fresh())
        ->is_active->toBeFalse()
        ->latitude->toBeNull()
        ->longitude->toBeNull()
        ->device_identifier->toBeNull()
        ->last_ip->toBeNull();

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/kiosk/info')
        ->assertStatus(401);

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/disconnect')
        ->assertStatus(401);
});

it('permite que un quiosco conectado realice el registro de asistencia', function () {
    [,, $device] = createKioskFixture();
    $token = $device->issueToken();

    $this->withToken($token)
        ->postJson('/api/v1/kiosk/connect', [
            'device_identifier' => 'tablet-001',
            'latitude' => -12.046374,
            'longitude' => -77.042793,
        ])
        ->assertOk();

    $this->withToken($token)
        ->postJson('/api/v1/attendance/mark', [
            'employee_code' => 'E-001',
            'request_id' => '6f1b1b3a-1c2d-4e5f-8a9b-0c1d2e3f4a5b',
            'device_identifier' => 'tablet-001',
            'latitude' => -12.046374,
            'longitude' => -77.042793,
        ])
        ->assertStatus(404)
        ->assertJsonMissing(['message' => 'Este token no puede acceder a esta ruta.']);
});

it('emite tokens para quiosco que incluyen prefijos y valores hash en la base de datos', function () {
    [,, $device] = createKioskFixture();
    $plain = $device->issueToken();

    expect($plain)->toStartWith('kiosk_')
        ->not->toContain('|');

    $stored = $device->tokens()->first();
    expect($stored)->not->toBeNull()
        ->and($stored->token)->toBe(hash('sha256', $plain))
        ->and($stored->token)->not->toBe($plain)
        ->and($stored->abilities)->toContain('kiosk');

    $this->withToken($plain)
        ->getJson('/api/v1/kiosk/info')
        ->assertStatus(409);
});
