<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;
    
    public function definition(): array
    {
        return [
            // Elige un Tenant existente de la base de datos
            'tenant_id'   => fn () => Tenant::inRandomOrder()->value('id'),
            'name'        => fake()->unique()->jobTitle(),
            'parent_id'   => null, // Por defecto es departamento raíz
            'description' => fake()->optional()->sentence(),
            'status'      => fake()->boolean(85),
        ];
    }
}
