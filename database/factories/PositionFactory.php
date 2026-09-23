<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

        public function definition(): array
        {
            // Busca un departamento existente en la BD para vincularlo dinámicamente
            $department = Department::inRandomOrder()->first();

            return [
                'tenant_id'     => $department?->tenant_id ?? Tenant::inRandomOrder()->value('id'),
                'department_id' => $department?->id,
                'name'          => fake()->unique()->jobTitle(),
                'description'   => fake()->optional()->sentence(),
                'status'        => fake()->boolean(90), // 90% activo
            ];
        }
}
