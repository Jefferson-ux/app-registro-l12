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
        Schema::create('attendance_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('employee_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('attendance_session_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->enum(
                'incident_type',
                [
                    'late',
                    'absence',
                    'early_leave',
                    'missing_check_in',
                    'missing_check_out',
                    'manual'
                ]
            );

            $table->date('incident_date');

            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'justified', 'approved', 'rejected', 'cancelled'])
                ->default('pending');

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('resolved_at')->nullable();

            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            $table->softDeletes();
            /* Añadido el campo deleted_at para permitir la eliminación suave de
            los registros de incidentes de asistencia, lo que permite mantener
             un historial de incidentes sin perder datos importantes.*/
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_incidents');
    }
};
