<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
        {
            $tenants = Tenant::all();

            if ($tenants->isEmpty()) {
                $this->command->error('No hay Tenants en la base de datos. Ejecuta TenantSeeder primero.');
                return;
            }

            // PASO 1: Garantizamos SÍ O SÍ mínimo 1 branch para cada tenant existente
            foreach ($tenants as $tenant) {
                Branch::factory()->create([
                    'tenant_id' => $tenant->id, // Forzamos este primer branch para este tenant
                ]);
            }

            // PASO 2: Generamos la cantidad extra que quieras al azar
            // Aquí el Factory usará su 'tenant_id' al azar leyendo la BD
            Branch::factory()->count(30)->create(); 
        }
}
