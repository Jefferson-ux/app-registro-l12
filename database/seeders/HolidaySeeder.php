<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        // Feriados oficiales de Perú (Fechas para 2026)
        $peruvianHolidays = [
            ['name' => 'Año Nuevo', 'date' => '2026-01-01'],
            ['name' => 'Jueves Santo', 'date' => '2026-04-02'],
            ['name' => 'Viernes Santo', 'date' => '2026-04-03'],
            ['name' => 'Día del Trabajador', 'date' => '2026-05-01'],
            ['name' => 'San Pedro y San Pablo', 'date' => '2026-06-29'],
            ['name' => 'Fiestas Patrias', 'date' => '2026-07-28'],
            ['name' => 'Fiestas Patrias', 'date' => '2026-07-29'],
            ['name' => 'Santa Rosa de Lima', 'date' => '2026-08-30'],
            ['name' => 'Combate de Angamos', 'date' => '2026-10-08'],
            ['name' => 'Todos los Santos', 'date' => '2026-11-01'],
            ['name' => 'Inmaculada Concepción', 'date' => '2026-12-08'],
            ['name' => 'Navidad del Señor', 'date' => '2026-12-25'],
        ];

        foreach ($tenants as $tenant) {
            foreach ($peruvianHolidays as $holiday) {
                Holiday::firstOrCreate(
                    [
                        'tenant_id'    => $tenant->id,
                        'holiday_date' => $holiday['date'],
                        'name'         => $holiday['name'],
                    ],
                    [
                        'branch_id'    => null, // Aplica a todas las sucursales por igual
                        'is_paid'      => true,
                    ]
                );
            }
        }
    }
}
