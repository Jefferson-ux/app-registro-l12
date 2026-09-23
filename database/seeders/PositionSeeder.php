<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants en la base de datos. Ejecuta TenantSeeder primero.');
            return;
        }

        foreach ($tenants as $tenant) {
            // Obtenemos los departamentos pertenecientes a este tenant
            $departments = Department::where('tenant_id', $tenant->id)->get();

            if ($departments->isEmpty()) {
                continue;
            }

            foreach ($departments as $department) {
                // Creamos entre 1 y 4 cargos (positions) por cada departamento
                Position::factory()
                    ->count(rand(1, 4))
                    ->create([
                        'tenant_id'     => $tenant->id,
                        'department_id' => $department->id,
                    ]);
            }
        }
    }
}
