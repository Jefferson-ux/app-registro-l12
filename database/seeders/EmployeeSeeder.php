<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        foreach ($tenants as $tenant) {
            // Cargar los registros existentes pertenecientes a este Tenant
            $branches    = Branch::where('tenant_id', $tenant->id)->pluck('id');
            $departments = Department::where('tenant_id', $tenant->id)->pluck('id');
            $positions   = Position::where('tenant_id', $tenant->id)->pluck('id');
            $users       = User::where('tenant_id', $tenant->id)->pluck('id');

            if ($branches->isEmpty() || $departments->isEmpty() || $positions->isEmpty()) {
                continue;
            }

            // ETAPA 1: Crear entre 10 y 20 empleados para el Tenant
            $employees = collect();
            $employeeCount = rand(10, 60);

            for ($i = 0; $i < $employeeCount; $i++) {
                // Asignar user_id solo a algunos empleados (no todos los empleados tienen acceso al sistema)
                $userId = ($i < $users->count()) ? $users[$i] : null;

                $employee = Employee::factory()->create([
                    'tenant_id'     => $tenant->id,
                    'user_id'       => $userId,
                    'branch_id'     => $branches->random(),
                    'department_id' => $departments->random(),
                    'position_id'   => $positions->random(),
                    'supervisor_id' => null, // Se asigna en la Etapa 2
                ]);

                $employees->push($employee);
            }

            // ETAPA 2: Asignar supervisores dentro del mismo Tenant
            // Designamos al primer empleado creado como el líder/jefe principal
            $topManager = $employees->first();

            foreach ($employees->slice(1) as $employee) {
                // 70% de probabilidad de tener un supervisor (el topManager o cualquier otro empleado del tenant)
                if (fake()->boolean(70)) {
                    $possibleSupervisors = $employees->where('id', '!=', $employee->id)->pluck('id');
                    
                    $employee->update([
                        'supervisor_id' => $possibleSupervisors->random() ?? $topManager->id,
                    ]);
                }
            }
        }
    }
}
