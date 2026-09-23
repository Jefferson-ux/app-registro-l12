<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        // Horarios base comunes que le sirven a cualquier empresa
        $defaultSchedules = [
            [
                'name' => 'Horario Administrativo (L-V)',
                'description' => 'Jornada estándar de 8:00 AM a 5:00 PM de Lunes a Viernes',
                'schedule_type' => 'fixed',
                'status' => true,
            ],
            [
                'name' => 'Turno Operativo Rotativo',
                'description' => 'Rotación semanal entre turno mañana y tarde',
                'schedule_type' => 'rotating',
                'status' => true,
            ],
            [
                'name' => 'Horario Flexible (Confianza)',
                'description' => 'Cumplimiento de 40 horas semanales sin marcado estricto',
                'schedule_type' => 'flexible',
                'status' => true,
            ],
        ];

        foreach ($tenants as $tenant) {
            // 1. Asignamos los horarios base a cada Tenant
            foreach ($defaultSchedules as $schedule) {
                WorkSchedule::firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'name'      => $schedule['name'],
                ], array_merge($schedule, ['tenant_id' => $tenant->id]));
            }

            // 2. Generamos de 1 a 3 horarios extras aleatorios por Tenant
            WorkSchedule::factory()->count(rand(1, 3))->create([
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
