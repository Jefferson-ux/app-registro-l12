<?php

namespace Database\Seeders\Testing;

use App\Models\Tenant;
use App\Models\WorkFlexibleRule;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Seeder;

class SchedulesSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'pruebas@empresa.pe')->firstOrFail();

        // 1. Horario fijo diurno (Lunes a Viernes 08:00 - 17:00)
        $fixedDaySchedule = WorkSchedule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Fijo Diurno (08:00 - 17:00)',
            'description' => 'Horario fijo de oficina con cierre automático a los 60 min.',
            'schedule_type' => 'fixed',
        ]);

        for ($day = 1; $day <= 7; $day++) {
            $isWorking = $day <= 5;

            WorkScheduleDay::create([
                'tenant_id' => $tenant->id,
                'work_schedule_id' => $fixedDaySchedule->id,
                'day_of_week' => $day,
                'is_working_day' => $isWorking,
                'check_in_time' => $isWorking ? '08:00:00' : null,
                'check_out_time' => $isWorking ? '17:00:00' : null,
                'break_start_time' => $isWorking ? '13:00:00' : null,
                'break_end_time' => $isWorking ? '14:00:00' : null,
                'check_in_tolerance_minutes' => 15,
                'check_out_tolerance_minutes' => 0,
                'auto_close_after_minutes' => 60,
            ]);
        }

        // 2. Horario fijo nocturno (cruza medianoche: 20:00 - 04:00)
        $fixedNightSchedule = WorkSchedule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Fijo Nocturno (20:00 - 04:00)',
            'description' => 'Horario fijo nocturno que cruza medianoche.',
            'schedule_type' => 'fixed',
        ]);

        for ($day = 1; $day <= 7; $day++) {
            $isWorking = $day <= 6;

            WorkScheduleDay::create([
                'tenant_id' => $tenant->id,
                'work_schedule_id' => $fixedNightSchedule->id,
                'day_of_week' => $day,
                'is_working_day' => $isWorking,
                'check_in_time' => $isWorking ? '20:00:00' : null,
                'check_out_time' => $isWorking ? '04:00:00' : null,
                'break_start_time' => $isWorking ? '00:00:00' : null,
                'break_end_time' => $isWorking ? '00:30:00' : null,
                'check_in_tolerance_minutes' => 10,
                'check_out_tolerance_minutes' => 0,
                'auto_close_after_minutes' => 60,
            ]);
        }

        // 3. Horario flexible con expected_days obligatorios (Lun, Mié, Vie)
        $flexibleExpectedSchedule = WorkSchedule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Flexible con Días Esperados (L-M-V)',
            'description' => 'Flexible con máximo de 480 min y faltas solo en Lun, Mié y Vie.',
            'schedule_type' => 'flexible',
        ]);

        WorkFlexibleRule::create([
            'tenant_id' => $tenant->id,
            'work_schedule_id' => $flexibleExpectedSchedule->id,
            'max_shift_minutes' => 480,
            'min_daily_minutes' => 240,
            'min_weekly_minutes' => 720,
            'entry_window_start' => null,
            'entry_window_end' => null,
            'requires_break' => false,
            'min_break_minutes' => null,
            'max_break_minutes' => null,
            'min_rest_between_shifts_minutes' => null,
            'expected_days' => [1, 3, 5],
        ]);

        // 4. Horario flexible libre (sin expected_days -> no genera faltas)
        $flexibleFreeSchedule = WorkSchedule::create([
            'tenant_id' => $tenant->id,
            'name' => 'Flexible Libre (Sin Días Esperados)',
            'description' => 'Flexible sin días obligatorios.',
            'schedule_type' => 'flexible',
        ]);

        WorkFlexibleRule::create([
            'tenant_id' => $tenant->id,
            'work_schedule_id' => $flexibleFreeSchedule->id,
            'max_shift_minutes' => 600,
            'min_daily_minutes' => 180,
            'min_weekly_minutes' => null,
            'entry_window_start' => null,
            'entry_window_end' => null,
            'requires_break' => false,
            'min_break_minutes' => null,
            'max_break_minutes' => null,
            'min_rest_between_shifts_minutes' => null,
            'expected_days' => null,
        ]);
    }
}
