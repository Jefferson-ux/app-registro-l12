<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'tenant_id'         => fn() => Tenant::inRandomOrder()->value('id'),
            'user_id'           => null, // Se asigna desde el seeder si el empleado tiene usuario
            'branch_id'         => null, // Asignado en el seeder según el tenant
            'department_id'     => null, // Asignado en el seeder según el tenant
            'position_id'       => null, // Asignado en el seeder según el tenant
            'supervisor_id'     => null, // Se asigna en segunda fase
            'employee_code'     => 'EMP-' . fake()->unique()->numberBetween(10000, 99999),
            'document_type'     => fake()->randomElement(['DNI', 'CE', 'PASAPORTE']),
            'document_number'   => fake()->unique()->numerify('########'),
            'first_name'        => fake()->firstName(),
            'last_name'         => fake()->lastName(),
            'personal_email'    => fake()->safeEmail(),
            'work_email'        => fake()->unique()->companyEmail(),
            'phone'             => fake()->phoneNumber(),
            'hire_date'         => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'termination_date'  => null,
            'employment_status' => fake()->randomElement(['active', 'active', 'active', 'active', 'active', 'inactive', 'suspended', 'terminated']),
        ];
    }
}
