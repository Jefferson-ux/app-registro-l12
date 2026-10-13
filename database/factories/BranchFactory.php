<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;
    
    public function definition(): array
    {
        return [
            // Lee los IDs de la base de datos y elige uno al azar
            'tenant_id'      => fn () => Tenant::inRandomOrder()->value('id'),
            'name'           => 'Sede ' . fake()->city(),
            'address'        => fake()->streetAddress(),
            'latitude'       => fake()->latitude(-18.0, -0.0),
            'longitude'      => fake()->longitude(-81.0, -68.0),
            'allowed_radius' => fake()->randomElement([50, 100, 200, 500]),
            'status'         => fake()->boolean(80)
        ];
    }
}
