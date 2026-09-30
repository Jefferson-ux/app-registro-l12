<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenantIds = Tenant::pluck('id');

        if ($tenantIds->isEmpty()) {
            return;
        }

        $defaultSchedules = [
            [
                'name'          => 'Horario Administrativo (L-V)',
                'description'   => 'Jornada estándar de 8:00 AM a 5:00 PM de Lunes a Viernes',
                'schedule_type' => 'fixed',
                'status'        => true,
            ],
            [
                'name'          => 'Turno Operativo Rotativo',
                'description'   => 'Rotación semanal entre turno mañana y tarde',
                'schedule_type' => 'rotating',
                'status'        => true,
            ],
            [
                'name'          => 'Horario Flexible (Confianza)',
                'description'   => 'Cumplimiento de 40 horas semanales sin marcado estricto',
                'schedule_type' => 'flexible',
                'status'        => true,
            ],
        ];

        $now = now()->toDateTimeString();
        $schedulesToInsert = [];

        foreach ($tenantIds as $tenantId) {
            // 1. Horarios base por empresa
            foreach ($defaultSchedules as $schedule) {
                $schedulesToInsert[] = array_merge($schedule, [
                    'tenant_id'  => $tenantId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2. Horarios extra aleatorios mediante Factory RAW
            $extraCount = rand(1, 3);
            for ($i = 0; $i < $extraCount; $i++) {
                $schedulesToInsert[] = WorkSchedule::factory()->raw([
                    'tenant_id'  => $tenantId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Inserción idempotente que evita duplicados si se corre múltiples veces
        WorkSchedule::upsert(
            $schedulesToInsert,
            ['tenant_id', 'name'],
            ['description', 'schedule_type', 'status', 'updated_at']
        );
    }
}