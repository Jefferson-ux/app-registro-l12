<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants en la base de datos. Ejecuta TenantSeeder primero.');
            return;
        }

        $now = now()->toDateTimeString();
        $positionsToInsert = [];

        foreach ($tenants as $tenant) {
            $departments = Department::where('tenant_id', $tenant->id)->pluck('id');

            if ($departments->isEmpty()) {
                continue;
            }

            foreach ($departments as $departmentId) {
                $count = rand(1, 4);

                for ($i = 0; $i < $count; $i++) {
                    $positionsToInsert[] = Position::factory()->raw([
                        'tenant_id'     => $tenant->id,
                        'department_id' => $departmentId,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }

        if (! empty($positionsToInsert)) {
            DB::transaction(function () use ($positionsToInsert) {
                foreach (array_chunk($positionsToInsert, 500) as $chunk) {
                    Position::insert($chunk);
                }
            });
        }
    }
}