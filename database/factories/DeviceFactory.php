<?php

namespace Database\Factories;

use App\Models\Device;
use App\Enums\DeviceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'branch_id' => null,
            'type' => DeviceType::Kiosk,
            'name' => fake()->unique()->word(),
            'latitude' => null,
            'longitude' => null,
            'device_identifier' => null,
            'is_active' => false,
            'last_used_at' => null,
            'last_ip' => null,
        ];
    }
}
