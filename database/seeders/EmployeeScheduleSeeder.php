<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants en la BD. Ejecuta TenantSeeder primero.');
            return;
        }

        foreach ($tenants as $tenant) {
            $employees = Employee::where('tenant_id', $tenant->id)->get();
            $schedules = WorkSchedule::where('tenant_id', $tenant->id)->where('status', true)->get();

            if ($employees->isEmpty() || $schedules->isEmpty()) {
                continue;
            }

            foreach ($employees as $employee) {
                $currentSchedule = $schedules->random();

                // 1. Horario Histórico (Opcional - 20% de probabilidad)
                if (fake()->boolean(20) && $schedules->count() > 1) {
                    $pastSchedule = $schedules->where('id', '!=', $currentSchedule->id)->random() ?? $currentSchedule;

                    EmployeeSchedule::create([
                        'tenant_id'        => $tenant->id,
                        'employee_id'      => $employee->id,
                        'work_schedule_id' => $pastSchedule->id,
                        'start_date'       => now()->subYear()->startOfYear()->format('Y-m-d'),
                        'end_date'         => now()->subYear()->endOfYear()->format('Y-m-d'),
                        'status'           => false,
                    ]);
                }

                // 2. Horario Vigente (Asignado a todos)
                EmployeeSchedule::create([
                    'tenant_id'        => $tenant->id,
                    'employee_id'      => $employee->id,
                    'work_schedule_id' => $currentSchedule->id,
                    'start_date'       => now()->startOfYear()->format('Y-m-d'),
                    'end_date'         => null, // Vigente
                    'status'           => true,
                ]);
            }
        }
    }
}