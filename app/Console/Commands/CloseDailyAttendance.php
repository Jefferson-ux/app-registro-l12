<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Employee;
use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CloseDailyAttendance extends Command
{
    protected $signature = 'attendance:close-daily {--date= : Fecha opcional en formato Y-m-d para reintentos o cierres manuales}';
    protected $description = 'Realiza el cierre diario de asistencia por tenant de forma masiva e idempotente.';

    public function handle()
    {
        $targetDate = $this->option('date') 
            ? Carbon::parse($this->option('date'))->toDateString() 
            : Carbon::today()->toDateString();
            //: Carbon::yesterday()->toDateString();

        $this->info("Iniciando proceso de cierre de asistencia para la fecha: {$targetDate}");

        Tenant::chunk(100, function ($tenants) use ($targetDate) {
            foreach ($tenants as $tenant) {
                try {
                    $this->processTenantClosure($tenant, $targetDate);
                } catch (\Exception $e) {
                    $this->error("Error procesando el tenant ID {$tenant->id}: " . $e->getMessage());
                    Log::error("Error en cierre de asistencia [Tenant: {$tenant->id}]: " . $e->getMessage());
                }
            }
        });

        $this->info("¡Proceso de cierre diario finalizado con éxito!");
        return self::SUCCESS;
    }

    protected function processTenantClosure(Tenant $tenant, string $targetDate): void
    {
        $this->line("Procesando Tenant: {$tenant->id}...");

        // 1. IDEMPOTENCIA: Verificar si ya se procesó este día para este tenant basándonos en tus enums reales
        $alreadyClosed = AttendanceSession::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('attendance_date', $targetDate)
            ->whereIn('status', ['present', 'late', 'absent', 'incomplete', 'holiday', 'leave'])
            ->exists();

        if ($alreadyClosed) {
            $this->line(" -> El tenant {$tenant->id} ya tiene registros de asistencia para {$targetDate}. Saltando...");
            return;
        }

        // 2. Buscar empleados activos de este tenant
        Employee::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('employment_status', 'active')
            ->chunkById(200, function ($employees) use ($targetDate) {
                foreach ($employees as $employee) {
                    $this->evaluateEmployeeAttendance($employee, $targetDate);
                }
            });
    }

    protected function evaluateEmployeeAttendance(Employee $employee, string $targetDate): void
    {
        $analysis = $employee->todayScheduleAnalysis();

        // Si hoy no le tocaba trabajar según su horario, ignoramos
        if (!$analysis['is_working_day_today']) {
            return;
        }

        $session = AttendanceSession::where('employee_id', $employee->id)
            ->where('attendance_date', $targetDate)
            ->first();

        if (!$session) {
            // CASO: Faltó por completo -> Usamos el enum 'absent' de tu base de datos
            AttendanceSession::create([
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
                'attendance_date' => $targetDate,
                'status' => 'absent', 
                'scheduled_minutes' => $this->calculateScheduledMinutes($analysis['schedule_details']),
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
            ]);
        } else {
            // CASO: Vino pero dejó la sesión abierta (olvidó marcar salida) -> Usamos 'incomplete'
            if ($session->check_in_at && !$session->check_out_at) {
                $session->update([
                    'check_out_at' => Carbon::parse($targetDate . ' 18:00:00'),
                    'status' => 'incomplete', 
                ]);
            }
        }
    }

    protected function calculateScheduledMinutes($scheduleDetails): int
    {
        if (!$scheduleDetails || !$scheduleDetails->check_in_time || !$scheduleDetails->check_out_time) {
            return 0;
        }

        $start = Carbon::parse($scheduleDetails->check_in_time);
        $end = Carbon::parse($scheduleDetails->check_out_time);

        return $start->diffInMinutes($end);
    }
}