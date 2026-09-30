<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        $now = now()->toDateTimeString();

        foreach ($tenants as $tenant) {
            $branches    = Branch::where('tenant_id', $tenant->id)->pluck('id');
            $departments = Department::where('tenant_id', $tenant->id)->pluck('id');
            $positions   = Position::where('tenant_id', $tenant->id)->pluck('id');
            $users       = User::where('tenant_id', $tenant->id)->pluck('id');

            if ($branches->isEmpty() || $departments->isEmpty() || $positions->isEmpty()) {
                continue;
            }

            $employeeCount = rand(10, 60);
            $employeesData = [];

            for ($i = 0; $i < $employeeCount; $i++) {
                $userId = ($i < $users->count()) ? $users[$i] : null;

                $employeesData[] = Employee::factory()->raw([
                    'tenant_id'     => $tenant->id,
                    'user_id'       => $userId,
                    'branch_id'     => $branches->random(),
                    'department_id' => $departments->random(),
                    'position_id'   => $positions->random(),
                    'supervisor_id' => null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            if (! empty($employeesData)) {
                Employee::insert($employeesData);

                $insertedEmployees = Employee::where('tenant_id', $tenant->id)->pluck('id');
                $topManagerId = $insertedEmployees->first();

                if ($insertedEmployees->count() > 1 && $topManagerId) {
                    $supervisorGroups = [];

                    foreach ($insertedEmployees->slice(1) as $employeeId) {
                        if (rand(1, 100) <= 70) {
                            $possibleSupervisors = $insertedEmployees->reject(fn($id) => $id === $employeeId);

                            $supervisorId = $possibleSupervisors->isNotEmpty()
                                ? $possibleSupervisors->random()
                                : $topManagerId;

                            $supervisorGroups[$supervisorId][] = $employeeId;
                        }
                    }

                    foreach ($supervisorGroups as $supervisorId => $employeeIds) {
                        Employee::whereIn('id', $employeeIds)->update(['supervisor_id' => $supervisorId]);
                    }
                }
            }
        }
    }
}