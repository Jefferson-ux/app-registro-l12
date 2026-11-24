<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_flexible_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_schedule_id')->unique()->constrained()->cascadeOnDelete();

            // Obligatorio: tiempo transcurrido desde la entrada tras el cual se cierra como incomplete.
            $table->unsignedSmallInteger('max_shift_minutes')->default(480);

            // Opcionales
            $table->unsignedSmallInteger('min_daily_minutes')->nullable();
            $table->unsignedSmallInteger('min_weekly_minutes')->nullable();

            $table->time('entry_window_start')->nullable();
            $table->time('entry_window_end')->nullable();

            $table->boolean('requires_break')->default(false);
            $table->unsignedSmallInteger('min_break_minutes')->nullable();
            $table->unsignedSmallInteger('max_break_minutes')->nullable();

            $table->unsignedSmallInteger('min_rest_between_shifts_minutes')->nullable();

            $table->json('expected_days')->nullable(); // null = sin inasistencias

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_flexible_rules');
    }
};
