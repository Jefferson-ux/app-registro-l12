<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenantIds = Tenant::pluck('id');

        if ($tenantIds->isEmpty()) {
            return;
        }

        $now = now()->toDateTimeString();

        // ETAPA 1: Generar departamentos raíz en memoria
        $rootDepartmentsToInsert = [];
        foreach ($tenantIds as $tenantId) {
            $count = rand(2, 4);
            for ($i = 0; $i < $count; $i++) {
                $rootDepartmentsToInsert[] = Department::factory()->raw([
                    'tenant_id'  => $tenantId,
                    'parent_id'  => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Inserción masiva de raíces (1 sola consulta)
        Department::insert($rootDepartmentsToInsert);

        // ETAPA 2: Obtener los IDs raíz en 1 sola consulta
        $rootDepartments = Department::whereNull('parent_id')->select('id', 'tenant_id')->get();

        // Generar sub-departamentos amarrados a los raíz
        $subDepartmentsToInsert = [];
        foreach ($rootDepartments as $parentDept) {
            $count = rand(0, 4);
            for ($i = 0; $i < $count; $i++) {
                $subDepartmentsToInsert[] = Department::factory()->raw([
                    'tenant_id'  => $parentDept->tenant_id,
                    'parent_id'  => $parentDept->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Inserción masiva de sub-departamentos (1 sola consulta)
        if (! empty($subDepartmentsToInsert)) {
            Department::insert($subDepartmentsToInsert);
        }
    }
}