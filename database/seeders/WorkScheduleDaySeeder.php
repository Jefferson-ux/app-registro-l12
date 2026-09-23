<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkScheduleDaySeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants en la BD. Ejecuta TenantSeeder primero.');
            return;
        }

        foreach ($tenants as $tenant) {
            $schedules = WorkSchedule::where('tenant_id', $tenant->id)->get();

            foreach ($schedules as $schedule) {
                // Iteramos exactamente los 7 días de la semana (1 = Lunes ... 7 = Domingo)
                for ($day = 1; $day <= 7; $day++) {

                    // Lunes (1) a Viernes (5) son laborables por defecto
                    // Sábado (6) y Domingo (7) son de descanso
                    $isWorkingDay = $day <= 5;

                    WorkScheduleDay::firstOrCreate(
                        [
                            'tenant_id'        => $tenant->id,
                            'work_schedule_id' => $schedule->id,
                            'day_of_week'      => $day,
                        ],
                        [
                            'is_working_day'              => $isWorkingDay,
                            'check_in_time'               => $isWorkingDay ? '08:00:00' : null,
                            'check_out_time'              => $isWorkingDay ? '17:00:00' : null,
                            'break_start_time'            => $isWorkingDay ? '13:00:00' : null,
                            'break_end_time'              => $isWorkingDay ? '14:00:00' : null,
                            'check_in_tolerance_minutes'  => $isWorkingDay ? 15 : 0,
                            'check_out_tolerance_minutes' => 0,
                        ]
                    );
                }
            }
        }
    }
}
