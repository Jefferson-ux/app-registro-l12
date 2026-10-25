<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ruta absoluta de tu archivo SQL
        $path = database_path('queries/employee_schedules_views.sql');

        if (file_exists($path)) {
            // 2. Leemos el contenido del SQL
            $sql = file_get_contents($path);

            // 3. Lo ejecutamos de forma directa (unprepared es ideal para estructuras complejas)
            DB::unprepared($sql);
        } else {
            throw new \Exception("No se encontró el archivo SQL en: {$path}");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Es una buena práctica eliminar la vista si decides hacer un rollback
        DB::unprepared("DROP VIEW IF EXISTS v_employee_schedules;");
    }
};
