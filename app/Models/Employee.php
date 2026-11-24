<?php

namespace App\Models;

use App\Services\Attendance\SchedulePolicyResolver;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes, BelongsToTenant, HasFactory, Auditable;

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
        'face_photo_path',
        'face_descriptor',
        'qr_image_path',
    ];

    protected $hidden = ['face_descriptor'];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
            'face_descriptor' => 'encrypted:array',
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
        // NUEVO FILTRO DE SEGURIDAD: Si el empleado no está ACTIVO en la empresa,
        // se corta la consulta y no se le otorga ningún horario vigente hoy.
        if ($this->employment_status !== 'active') {
            return null;
        }

        $today = $this->todayInTenantTimezone()->toDateString();

        return $this->schedules()
            ->where('status', true)
            ->whereDate('start_date', '<=', $today)
            ->where(
                fn($query) =>
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today)
            )
            ->latest('start_date')
            ->first();
    }

    public function hasWorkScheduleToday(): bool
    {
        return app(SchedulePolicyResolver::class)
            ->forEmployee($this)
            ->worksOn($this, $this->todayInTenantTimezone());
    }


    public function hasBreakToday(): bool
    {
        $day = $this->activeSchedule()
            ?->workSchedule
            ?->days()
            ->where('day_of_week', $this->todayInTenantTimezone()->dayOfWeekIso)
            ->first();

        // Evalúa si el día encontrado tiene horas de descanso asignadas
        // Retorna TRUE solo si la hora de inicio Y la hora de fin NO son nulas (ambas deben existir)
        return $day?->break_start_time !== null && $day?->break_end_time !== null;
    }

    public function todayScheduleAnalysis(): array
    {
        $activeSchedule = $this->activeSchedule();

        // Reutilizamos la lógica de las funciones de arriba pasándoles el horario en memoria si fuera necesario,
        // o llamándolas directamente ya que Eloquent guarda en caché las relaciones repetidas en la misma petición.
        $policy = app(SchedulePolicyResolver::class)->forEmployee($this);

        return [
            'has_active_contract'  => $activeSchedule !== null,
            'is_working_day_today' => $this->hasWorkScheduleToday(), // Reutiliza el filtro de NULLs
            'has_break_today'      => $this->hasBreakToday(),        // Reutiliza el filtro de breaks
            'blocked_reason'       => $policy->blockedReason(),

            // Traemos el detalle de forma limpia reutilizando la misma jerarquía
            'schedule_details'     => $activeSchedule?->workSchedule
                ?->days()
                ->where('day_of_week', $this->todayInTenantTimezone()->dayOfWeekIso)
                ->first(),
        ];
    }

    private function todayInTenantTimezone(): Carbon
    {
        return Carbon::now($this->tenant?->timezone ?? config('app.timezone'));
    }
}
