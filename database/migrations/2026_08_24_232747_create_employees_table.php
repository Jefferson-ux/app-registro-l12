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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Relación obligatoria con el inquilino
            $table->foreignId('tenant_id')
                ->constrained()
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Relaciones opcionales (por si un empleado no tiene usuario o área asignada aún)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete()->cascadeOnUpdate();

            // Relación con su jefe inmediato (Auto-relación a la misma tabla)
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete()->cascadeOnUpdate();

            $table->string('employee_code', 50);
            $table->string('document_type', 30)->nullable();
            $table->string('document_number', 50)->nullable();

            $table->string('first_name', 100);
            $table->string('last_name', 100);

            $table->string('personal_email', 150)->nullable();
            $table->string('work_email', 150)->nullable();

            $table->string('phone', 50)->nullable();

            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();

            // Se cambia ENUM por string + valor por defecto para flexibilidad futura
            $table->enum('employment_status', ['active', 'inactive', 'suspended', 'terminated'])
                ->default('active');

            $table->timestamps();
            $table->softDeletes();

            // Clave única compuesta: Código de empleado único por cada empresa
            $table->unique(['tenant_id', 'employee_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
