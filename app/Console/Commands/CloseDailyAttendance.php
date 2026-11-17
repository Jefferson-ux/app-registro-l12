<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\Attendance\FindScheduleDayAction;
use App\Models\Tenant;
use App\Models\Employee;
use App\Models\AttendanceSession;
use App\Models\WorkScheduleDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CloseDailyAttendance extends Command
{
    protected $signature = 'attendance:close-daily
        {--date= : Fecha opcional en formato dd/mm/yy para reintentos o cierres manuales}
        {--tenant-id= : ID opcional para procesar un solo tenant}';
    protected $description = 'Realiza el cierre diario de asistencia por tenant de forma masiva e idempotente.';

    public function handle(FindScheduleDayAction $findScheduleDay)
    {
        $dateOption = $this->option('date');

        if ($dateOption !== null && $dateOption !== '') {
            if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/D', $dateOption) !== 1) {
                $this->error('La fecha debe tener el formato dd/mm/yy, por ejemplo 07/11/26.');
                return self::FAILURE;
            }

            $parsedDate = Carbon::createFromFormat('!d/m/y', $dateOption);
            $dateErrors = Carbon::getLastErrors();

            if (!$parsedDate || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                $this->error('La fecha indicada no es válida. Usa el formato dd/mm/yy, por ejemplo 07/11/26.');
                return self::FAILURE;
            }

            $targetDate = $parsedDate->toDateString();
        } else {
            $targetDate = Carbon::today()->toDateString();
        }
        //: Carbon::yesterday()->toDateString();

        $tenantId = $this->option('tenant-id');

        if ($tenantId !== null && (!ctype_digit((string) $tenantId) || (int) $tenantId < 1)) {
            $this->error('El tenant-id debe ser un número entero positivo.');
            return self::FAILURE;
        }

        if ($tenantId !== null && !Tenant::whereKey($tenantId)->exists()) {
            $this->error("No existe un tenant con ID {$tenantId}.");
            return self::FAILURE;
        }

        $this->info("Iniciando proceso de cierre de asistencia para la fecha: {$targetDate}");

        Tenant::query()
            ->when($tenantId !== null, fn($query) => $query->whereKey($tenantId))
            ->chunk(10, function ($tenants) use ($targetDate, $findScheduleDay) {
                foreach ($tenants as $tenant) {
                    try {
                        $this->processTenantClosure($tenant, $targetDate, $findScheduleDay);
                    } catch (\Exception $e) {
                        $this->error("Error procesando el tenant ID {$tenant->id}: " . $e->getMessage());
                        Log::error("Error en cierre de asistencia [Tenant: {$tenant->id}]: " . $e->getMessage());
                    }
                }
            });

        $this->info("¡Proceso de cierre diario finalizado con éxito!");
        return self::SUCCESS;
    }

    protected function processTenantClosure(Tenant $tenant, string $targetDate, FindScheduleDayAction $findScheduleDay): void
    {
        $this->line("Procesando Tenant: {$tenant->id} - {$tenant->name}...");

        // Revisar a cada empleado permite completar registros faltantes aunque ya existan otros ese día.
        Employee::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('employment_status', 'active')
            ->chunkById(200, function ($employees) use ($targetDate, $findScheduleDay) {
                foreach ($employees as $employee) {
                    $this->evaluateEmployeeAttendance($employee, $targetDate, $findScheduleDay);
                }
            });
    }

    protected function evaluateEmployeeAttendance(Employee $employee, string $targetDate, FindScheduleDayAction $findScheduleDay): void
    {
        $scheduleDay = $findScheduleDay->execute(
            $employee,
            $targetDate,
            Carbon::parse($targetDate)->dayOfWeekIso
        );

        if (!$scheduleDay || !$scheduleDay->is_working_day) {
            return;
        }

        $session = AttendanceSession::withoutGlobalScopes()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->where('attendance_date', $targetDate)
            ->first();

        if (!$session) {
            // CASO: Faltó por completo -> Usamos el enum 'absent' de tu base de datos
            AttendanceSession::create([
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
                'attendance_date' => $targetDate,
                'status' => 'absent',
                'scheduled_minutes' => $this->calculateScheduledMinutes($scheduleDay),
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
            ]);
        } elseif ($session->check_in_at) {
            $updates = [];

            if (!$session->check_out_at) {
                $updates['status'] = 'incomplete';

                if ($scheduleDay->check_out_time) {
                    $updates['check_out_at'] = $this->scheduleTime($targetDate, $scheduleDay->check_out_time);
                } else {
                    $this->warn(" -> El empleado {$employee->id} no tiene hora de salida configurada; la sesión queda sin cierre.");
                }
            }

            if ($session->break_start_at && !$session->break_end_at) {
                $updates['status'] = 'incomplete';
            }

            if ($updates) {
                $session->update($updates);
            }
        }
    }

    protected function calculateScheduledMinutes(WorkScheduleDay $scheduleDetails): int
    {
        if (!$scheduleDetails->check_in_time || !$scheduleDetails->check_out_time) {
            return 0;
        }

        $start = Carbon::parse($scheduleDetails->check_in_time);
        $end = Carbon::parse($scheduleDetails->check_out_time);
        $scheduledMinutes = $start->diffInMinutes($end);

        if ($scheduleDetails->break_start_time && $scheduleDetails->break_end_time) {
            $breakStart = Carbon::parse($scheduleDetails->break_start_time);
            $breakEnd = Carbon::parse($scheduleDetails->break_end_time);
            $scheduledMinutes -= $breakStart->diffInMinutes($breakEnd);
        }

        return max(0, $scheduledMinutes);
    }

    private function scheduleTime(string $date, \DateTimeInterface|string $time): Carbon
    {
        return Carbon::parse($date . ' ' . Carbon::parse($time)->format('H:i:s'));
    }
}
