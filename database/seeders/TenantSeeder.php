<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Generamos 10 empresas dinámicas sin repeticiones de datos
        for ($i = 1; $i <= 6; $i++) {
            $companyName = fake()->unique()->company();

            Tenant::create([
                'uuid' => fake()->uuid(),
                'name' => $companyName,
                'business_name' => Str::lower($companyName) . ' s.a.c.',
                'tax_id' => '20' . fake()->unique()->numerify('#########'), // RUC peruano único (11 dígitos)
                'email' => fake()->unique()->companyEmail(),
                'phone' => '+51 ' . fake()->numerify('9########'),
                'country' => 'Peru',
                'timezone' => 'America/Lima',
                'logo' => null, // Permite que Filament aplique defaultImageUrl()
                'status' => fake()->randomElement(['trial','active','suspended','inactive']),
                'trial_ends_at' => now()->addDays(14),
            ]);
        }
    }
}