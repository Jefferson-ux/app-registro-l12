<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_schedule_days', function (Blueprint $table) {
            // Minutos después de check_out_time en que la sesión se cierra sola.
            $table->unsignedSmallInteger('auto_close_after_minutes')
                ->default(60)
                ->after('check_out_tolerance_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('work_schedule_days', function (Blueprint $table) {
            $table->dropColumn('auto_close_after_minutes');
        });
    }
};
