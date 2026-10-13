<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenantIds = Tenant::pluck('id');

        if ($tenantIds->isEmpty()) {
            return;
        }

        $now = now()->toDateTimeString();
        $branchesToInsert = [];

        // PASO 1: Mínimo 1 sucursal garantizada por empresa
        foreach ($tenantIds as $tenantId) {
            $branchesToInsert[] = Branch::factory()->raw([
                'tenant_id'  => $tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // PASO 2: 30 sucursales extra asignadas al azar
        for ($i = 0; $i < 30; $i++) {
            $branchesToInsert[] = Branch::factory()->raw([
                'tenant_id'  => $tenantIds->random(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Inserción masiva única (1 consulta SQL)
        Branch::insert($branchesToInsert);
    }
}