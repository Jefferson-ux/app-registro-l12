<?php

use App\Models\Device;

uses(Tests\TestCase::class);

it('reports online state from activity and connection state', function () {
    $device = new Device([
        'name' => 'Prueba',
    ]);

    $device->is_active = false;
    $device->last_used_at = now();
    expect($device->is_online)->toBeFalse();

    $device->is_active = true;
    $device->last_used_at = now()->subSeconds(179);
    expect($device->is_online)->toBeTrue();

    $device->last_used_at = now()->subSeconds(181);
    expect($device->is_online)->toBeFalse();
});
