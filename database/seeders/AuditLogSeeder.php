<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        // 1. Obtener IDs en memoria con solo 2 consultas
        $tenantIds = Tenant::pluck('id');
        $userIds   = User::pluck('id');

        if ($tenantIds->isEmpty() || $userIds->isEmpty()) {
            return;
        }

        $logsToInsert = [];
        $totalLogs = 200;

        // 2. Generar datos en memoria
        for ($i = 0; $i < $totalLogs; $i++) {
            $logsToInsert[] = AuditLog::factory()->raw([
                'tenant_id' => $tenantIds->random(),
                'user_id'   => $userIds->random(),
            ]);
        }

        // 3. Inserción masiva única (1 sola consulta SQL)
        DB::transaction(function () use ($logsToInsert) {
            foreach (array_chunk($logsToInsert, 500) as $chunk) {
                AuditLog::insert($chunk);
            }
        });
    }
}