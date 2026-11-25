<?php

namespace Database\Seeders\Testing;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class EmployeesAndSchedulesSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'pruebas@empresa.pe')->firstOrFail();

        $branchIds = Branch::where('tenant_id', $tenant->id)->pluck('id');

        if ($branchIds->isEmpty()) {
            $branchIds = collect([
                Branch::create(Branch::factory()->raw([
                    'tenant_id' => $tenant->id,
                ]))->id,
            ]);
        }

        $adminDept = Department::where('tenant_id', $tenant->id)->where('name', 'Administración')->firstOrFail();
        $opsDept = Department::where('tenant_id', $tenant->id)->where('name', 'Operaciones')->firstOrFail();
        $fieldDept = Department::where('tenant_id', $tenant->id)->where('name', 'Campo y Jornales')->firstOrFail();

        $adminPos = Position::where('tenant_id', $tenant->id)->where('name', 'Administrativo')->firstOrFail();
        $operatorPos = Position::where('tenant_id', $tenant->id)->where('name', 'Operador de Planta')->firstOrFail();
        $dayLaborerPos = Position::where('tenant_id', $tenant->id)->where('name', 'Jornalero')->firstOrFail();

        $fixedDay = WorkSchedule::where('tenant_id', $tenant->id)->where('name', 'like', 'Fijo Diurno%')->firstOrFail();
        $fixedNight = WorkSchedule::where('tenant_id', $tenant->id)->where('name', 'like', 'Fijo Nocturno%')->firstOrFail();
        $flexExpected = WorkSchedule::where('tenant_id', $tenant->id)->where('name', 'like', 'Flexible con Días%')->firstOrFail();
        $flexFree = WorkSchedule::where('tenant_id', $tenant->id)->where('name', 'like', 'Flexible Libre%')->firstOrFail();

        $jornaleroCounter = 1;

        for ($i = 1; $i <= 20; $i++) {
            if ($i <= 5) {
                $departmentId = $adminDept->id;
                $positionId = $adminPos->id;
                $scheduleId = $fixedDay->id;
                $employeeCode = 'ADM-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            } elseif ($i <= 10) {
                $departmentId = $opsDept->id;
                $positionId = $operatorPos->id;
                $scheduleId = $i % 2 === 0 ? $fixedNight->id : $fixedDay->id;
                $employeeCode = 'EMP-' . str_pad((string) ($i - 5), 3, '0', STR_PAD_LEFT);
            } elseif ($i <= 15) {
                $departmentId = $fieldDept->id;
                $positionId = $dayLaborerPos->id;
                $scheduleId = $flexExpected->id;
                $employeeCode = str_pad((string) $jornaleroCounter++, 3, '0', STR_PAD_LEFT);
            } else {
                $departmentId = $fieldDept->id;
                $positionId = $dayLaborerPos->id;
                $scheduleId = $flexFree->id;
                $employeeCode = str_pad((string) $jornaleroCounter++, 3, '0', STR_PAD_LEFT);
            }

            $employee = Employee::factory()->create([
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'branch_id' => $branchIds->random(),
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'supervisor_id' => null,
                'employee_code' => $employeeCode,
                'hire_date' => now()->subMonths(3)->toDateString(),
                'termination_date' => null,
                'employment_status' => 'active',
            ]);

            EmployeeSchedule::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'work_schedule_id' => $scheduleId,
                'start_date' => now()->subMonths(3)->toDateString(),
                'end_date' => null,
                'status' => true,
            ]);
        }
    }
}
