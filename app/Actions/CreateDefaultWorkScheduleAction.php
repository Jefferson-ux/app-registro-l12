<?php

namespace App\Actions;

use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;

class CreateDefaultWorkScheduleAction
{
    public function execute(int $tenantId): WorkSchedule
    {
        $now = now();

        $schedule = WorkSchedule::create([
            'tenant_id'   => $tenantId,
            'name'        => 'Horario Administrativo (L-V)',
            'description' => 'Jornada estándar de 8:00 AM a 5:00 PM de Lunes a Viernes',
            'type'        => 'fixed',
            'status'   => true, 
        ]);

        $workingDay = [
            'is_working_day'            => 1,
            'check_in_time'             => '08:00:00',
            'check_out_time'            => '17:00:00',
            'break_start_time'          => '13:00:00',
            'break_end_time'            => '14:00:00',
            'check_in_tolerance_minutes'  => 10,
            'check_out_tolerance_minutes' => 0,
        ];

        $restDay = [
            'is_working_day'            => 0,
            'check_in_time'             => null,
            'check_out_time'            => null,
            'break_start_time'          => null,
            'break_end_time'            => null,
            'check_in_tolerance_minutes'  => 0,
            'check_out_tolerance_minutes' => 0,
        ];

        $rows = [];
        for ($day = 1; $day <= 7; $day++) {
            $rows[] = [
                'tenant_id'        => $tenantId,
                'work_schedule_id' => $schedule->id,
                'day_of_week'      => $day,
                'created_at'       => $now,
                'updated_at'       => $now,
            ] + ($day <= 5 ? $workingDay : $restDay);
        }

        WorkScheduleDay::insert($rows); // una sola consulta

        return $schedule;
    }
}