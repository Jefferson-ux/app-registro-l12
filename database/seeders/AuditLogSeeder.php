<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        // Genera 200 registros de auditoría usando el molde aleatorio
        AuditLog::factory()->count(200)->create();
    }
}
