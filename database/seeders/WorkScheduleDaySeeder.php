<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkScheduleDaySeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        // 1. Obtenemos solo los IDs necesarios de todos los horarios
        $schedules = WorkSchedule::all(['id', 'tenant_id']);

        if ($schedules->isEmpty()) {
            return;
        }

        $now = now()->toDateTimeString();

        // 2. Traemos combinaciones existentes a memoria en 1 sola consulta
        $existing = WorkScheduleDay::select('work_schedule_id', 'day_of_week')
            ->get()
            ->mapWithKeys(fn($item) => ["{$item->work_schedule_id}|{$item->day_of_week}" => true])
            ->toArray();

        $daysToInsert = [];

        foreach ($schedules as $schedule) {
            for ($day = 1; $day <= 7; $day++) {
                $key = "{$schedule->id}|{$day}";

                if (isset($existing[$key])) {
                    continue; // Ya existe en la BD, se omite
                }

                $isWorkingDay = $day <= 5;

                $daysToInsert[] = [
                    'tenant_id'                   => $schedule->tenant_id,
                    'work_schedule_id'            => $schedule->id,
                    'day_of_week'                 => $day,
                    'is_working_day'              => $isWorkingDay,
                    'check_in_time'               => $isWorkingDay ? '08:00:00' : null,
                    'check_out_time'              => $isWorkingDay ? '17:00:00' : null,
                    'break_start_time'            => $isWorkingDay ? '13:00:00' : null,
                    'break_end_time'              => $isWorkingDay ? '14:00:00' : null,
                    'check_in_tolerance_minutes'  => $isWorkingDay ? 15 : 0,
                    'check_out_tolerance_minutes' => 0,
                    'created_at'                  => $now,
                    'updated_at'                  => $now,
                ];
            }
        }

        // 3. Inserción masiva única
        if (! empty($daysToInsert)) {
            DB::transaction(function () use ($daysToInsert) {
                foreach (array_chunk($daysToInsert, 500) as $chunk) {
                    WorkScheduleDay::insert($chunk);
                }
            });
        }
    }
}