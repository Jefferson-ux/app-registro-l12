<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes, BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'branch_id',
        'department_id',
        'position_id',
        'supervisor_id',
        'employee_code',
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'personal_email',
        'work_email',
        'phone',
        'hire_date',
        'termination_date',
        'employment_status',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function attendanceIncidents(): HasMany
    {
        return $this->hasMany(AttendanceIncident::class);
    }


    /*
    public function hasWorkScheduleToday(): bool
    {
        // 1. ¿Tiene un contrato/horario vigente el día de hoy?
        $activeSchedule = $this->activeSchedule();

        if (!$activeSchedule) {
            return false;
        }

        // 2. Buscamos el día actual en su configuración (ej: hoy sábado es 6)
        $dayConfig = $activeSchedule->workSchedule
            ?->days()
            ->where('day_of_week', today()->dayOfWeekIso)
            ->first();

        // 3. Si no existe el registro de ese día, no trabaja hoy
        if (!$dayConfig) {
            return false;
        }

        // OPCIONAL: Si guardas todos los 7 días pero usas un campo para activarlos/desactivarlos
        // Si tu tabla no tiene este campo (is_working o activo), puedes borrar esta línea:
        if (isset($dayConfig->is_working) && !$dayConfig->is_working) {
            return false;
        }

        return true;
    }*/

    public function activeSchedule(): ?EmployeeSchedule
    {
        // Accede a la relación de todos los horarios vinculados a este empleado
        return $this->schedules()
            // Filtra solo los horarios que estén con estado "activo/habilitado" (true)
            ->where('status', true)
            // Se asegura de que el horario ya haya comenzado (start_date debe ser hoy o en el pasado)
            ->whereDate('start_date', '<=', today())
            // Aplica un filtro agrupado para evaluar la fecha de finalización (end_date)
            ->where(
                fn($query) =>
                // Caso A: El horario es indefinido (no tiene fecha de término, end_date es NULL)
                $query->whereNull('end_date')
                    // Caso B: O si tiene fecha de término, esta vencerá hoy o en el futuro (>= hoy)
                    ->orWhereDate('end_date', '>=', today())
            )
            // Si el empleado tiene más de un horario válido hoy, los ordena poniendo el más nuevo primero
            ->latest('start_date')
            // Toma únicamente el primer registro de la lista ordenada y cierra la consulta
            ->first();
    }

    public function hasWorkScheduleToday(): bool
    {
        $activeSchedule = $this->activeSchedule();

        if (!$activeSchedule) {
            return false;
        }

        $dayConfig = $activeSchedule->workSchedule
            ?->days()
            ->where('day_of_week', today()->dayOfWeekIso)
            ->first();

        // Si no existe la configuración, o si explícitamente viene "is_working_day" en false, no trabaja hoy
        if (!$dayConfig || !$dayConfig->is_working_day) {
            return false;
        }

        // Mantenemos por seguridad que si está marcado como laborable, tenga horas válidas
        /*if ($dayConfig->check_in_time === null || $dayConfig->check_out_time === null) {
            return false;
        }*/

        return true;
    }


    public function hasBreakToday(): bool
    {
        // Busca la configuración del día actual siguiendo una cadena de relaciones seguras (?->)
        $day = $this->activeSchedule() // 1. Obtiene el horario vigente de hoy usando la función de arriba
            ?->workSchedule            // 2. Accede a la jornada o plantilla horaria asociada a ese horario
            ?->days()                  // 3. Entra a la lista de días configurados para esa jornada (Lunes a Domingo)
            ->where('day_of_week', today()->dayOfWeekIso) // 4. Filtra por el día actual (ISO: Lunes=1, Domingo=7)
            ->first();                 // 5. Toma el registro de ese día específico y lo guarda en $day

        // Evalúa si el día encontrado tiene horas de descanso asignadas
        // Retorna TRUE solo si la hora de inicio Y la hora de fin NO son nulas (ambas deben existir)
        return $day?->break_start_time !== null && $day?->break_end_time !== null;
    }

    public function todayScheduleAnalysis(): array
    {
        $activeSchedule = $this->activeSchedule();

        // Reutilizamos la lógica de las funciones de arriba pasándoles el horario en memoria si fuera necesario,
        // o llamándolas directamente ya que Eloquent guarda en caché las relaciones repetidas en la misma petición.
        return [
            'has_active_contract'  => $activeSchedule !== null,
            'is_working_day_today' => $this->hasWorkScheduleToday(), // Reutiliza el filtro de NULLs
            'has_break_today'      => $this->hasBreakToday(),        // Reutiliza el filtro de breaks

            // Traemos el detalle de forma limpia reutilizando la misma jerarquía
            'schedule_details'     => $activeSchedule?->workSchedule
                ?->days()
                ->where('day_of_week', today()->dayOfWeekIso)
                ->first(),
        ];
    }
}
