<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkSchedule>
 */
class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return [
            'tenant_id'     => fn () => Tenant::inRandomOrder()->value('id'),
            'name'          => fake()->randomElement([
                'Turno Mañana (Oficina)',
                'Turno Tarde',
                'Turno Noche',
                'Horario Flexible',
                'Jornada Reducida',
                'Turno Rotativo'
            ]),
            'description'   => fake()->optional()->sentence(),
            'schedule_type' => fake()->randomElement(['fixed', 'flexible', 'rotating']),
            'status'        => fake()->boolean(90), // 90% activos
        ];
    }
}
