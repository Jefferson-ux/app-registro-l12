<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_schedule_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            // Añadido por temas de integridad referencial y para mantener la relación con la tabla de work_schedules

            $table->foreignId('work_schedule_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->tinyInteger('day_of_week'); // 0 (Sunday) to 6 (Saturday)
            $table->boolean('is_working_day')->default(true);

            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();

            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();

            $table->integer('check_in_tolerance_minutes')->default(0);
            $table->integer('check_out_tolerance_minutes')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedule_days');
    }
};
