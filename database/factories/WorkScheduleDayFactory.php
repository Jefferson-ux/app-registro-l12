<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkScheduleDay>
 */
class WorkScheduleDayFactory extends Factory
{
    protected $model = WorkScheduleDay::class;

    public function definition(): array
    {
        $schedule = WorkSchedule::inRandomOrder()->first();

        return [
            'tenant_id'                   => $schedule?->tenant_id ?? Tenant::inRandomOrder()->value('id'),
            'work_schedule_id'            => $schedule?->id,
            'day_of_week'                 => fake()->numberBetween(1, 7), // 1 = Lunes, 7 = Domingo
            'is_working_day'              => true,
            'check_in_time'               => '08:00:00',
            'check_out_time'              => '17:00:00',
            'break_start_time'            => '13:00:00',
            'break_end_time'              => '14:00:00',
            'check_in_tolerance_minutes'  => 15, // 15 min de tolerancia para tardanzas
            'check_out_tolerance_minutes' => 0,
        ];
    }
}
