<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSchedule>
 */
class EmployeeScheduleFactory extends Factory
{
    protected $model = EmployeeSchedule::class;

    public function definition(): array
    {
        $employee = Employee::inRandomOrder()->first();

        return [
            'tenant_id'        => $employee?->tenant_id ?? Tenant::inRandomOrder()->value('id'),
            'employee_id'      => $employee?->id,
            'work_schedule_id' => $employee 
                ? WorkSchedule::where('tenant_id', $employee->tenant_id)->inRandomOrder()->value('id')
                : null,
            'start_date'       => now()->startOfYear()->format('Y-m-d'),
            'end_date'         => null, // Null indica que es el horario vigente
            'status'           => true,
        ];
    }
}
