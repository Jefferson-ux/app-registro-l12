<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        foreach ($tenants as $tenant) {
            // ETAPA 1: Crear departamentos principales/raíz (Sin padre)
            $rootDepartments = Department::factory()->count(rand(2, 4))->create([
                'tenant_id' => $tenant->id,
                'parent_id' => null,
            ]);

            // ETAPA 2: Crear sub-departamentos amarrados a los raíz del mismo Tenant
            foreach ($rootDepartments as $parentDept) {
                // Genera entre 0 y 4 subdepartamentos por cada departamento raíz
                Department::factory()->count(rand(0, 4))->create([
                    'tenant_id' => $tenant->id,
                    'parent_id' => $parentDept->id, // Asigna un padre real que NO es él mismo
                ]);
            }
        }
    }
}