<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $employee = Employee::inRandomOrder()->first();
        $method = fake()->randomElement(['web', 'mobile', 'kiosk', 'qr', 'manual', 'biometric', 'api']);

        return [
            'tenant_id'         => $employee?->tenant_id ?? Tenant::inRandomOrder()->value('id'),
            'employee_id'       => $employee?->id,
            'branch_id'         => $employee?->branch_id ?? Branch::inRandomOrder()->value('id'),
            'type'              => fake()->randomElement(['check_in', 'check_out', 'break_start', 'break_end']),
            'recorded_at'       => fake()->dateTimeBetween('-15 days', 'now'),
            'method'            => $method,
            'latitude'          => in_array($method, ['mobile', 'kiosk']) ? fake()->latitude() : null,
            'longitude'         => in_array($method, ['mobile', 'kiosk']) ? fake()->longitude() : null,
            'ip_address'        => fake()->ipv4(),
            'device_identifier' => 'DEV-' . fake()->bothify('??###'),
            'notes'             => fake()->boolean(15) ? fake()->sentence() : null,
        ];
    }
}
