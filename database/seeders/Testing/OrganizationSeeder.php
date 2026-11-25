<?php

namespace Database\Seeders\Testing;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenant = Tenant::where('email', 'pruebas@empresa.pe')->firstOrFail();
        $now = now()->toDateTimeString();

        $branchesToInsert = [
            Branch::factory()->raw([
                'tenant_id' => $tenant->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]),
            Branch::factory()->raw([
                'tenant_id' => $tenant->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]),
        ];

        Branch::insert($branchesToInsert);

        $departments = [
            'Administracion' => Department::create([
                'tenant_id' => $tenant->id,
                'name' => 'Administración',
                'parent_id' => null,
                'description' => 'Gestión administrativa y soporte interno.',
            ]),
            'Operaciones' => Department::create([
                'tenant_id' => $tenant->id,
                'name' => 'Operaciones',
                'parent_id' => null,
                'description' => 'Operación continua y turnos programados.',
            ]),
            'Campo' => Department::create([
                'tenant_id' => $tenant->id,
                'name' => 'Campo y Jornales',
                'parent_id' => null,
                'description' => 'Personal de campo con régimen flexible.',
            ]),
        ];

        Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $departments['Administracion']->id,
            'name' => 'Administrativo',
            'description' => 'Personal administrativo con horario fijo.',
        ]);

        Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $departments['Operaciones']->id,
            'name' => 'Operador de Planta',
            'description' => 'Personal de planta con horario fijo.',
        ]);

        Position::create([
            'tenant_id' => $tenant->id,
            'department_id' => $departments['Campo']->id,
            'name' => 'Jornalero',
            'description' => 'Trabajador con horario flexible.',
        ]);
    }
}
