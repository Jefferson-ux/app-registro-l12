<?php

namespace App\Console\Commands;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\Tenant;
use App\Services\Attendance\Contracts\SchedulePolicy;
use App\Services\Attendance\SchedulePolicyResolver;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseDailyAttendance extends Command
{
    protected $signature = 'attendance:close-daily
        {--date= : Fecha opcional en formato dd/mm/yy para reintentos o cierres manuales}
        {--tenant-id= : ID opcional para procesar un solo tenant}';

    protected $description = 'Realiza el cierre diario de asistencia por tenant de forma masiva e idempotente.';

    public function handle(SchedulePolicyResolver $policyResolver): int
    {
        $targetDate = $this->resolveTargetDate();

        if ($targetDate === null) {
            return self::FAILURE;
        }

        $tenantId = $this->option('tenant-id');

        if ($tenantId !== null && (! ctype_digit((string) $tenantId) || (int) $tenantId < 1)) {
            $this->error('El tenant-id debe ser un número entero positivo.');

            return self::FAILURE;
        }

        if ($tenantId !== null && ! Tenant::whereKey($tenantId)->exists()) {
            $this->error("No existe un tenant con ID {$tenantId}.");

            return self::FAILURE;
        }

        $this->info("Iniciando proceso de cierre de asistencia para la fecha: {$targetDate}");

        Tenant::query()
            ->when($tenantId !== null, fn($query) => $query->whereKey($tenantId))
            ->chunkById(10, function ($tenants) use ($targetDate, $policyResolver): void {
                foreach ($tenants as $tenant) {
                    try {
                        $this->processTenant($tenant, $targetDate, $policyResolver);
                    } catch (\Throwable $e) {
                        $this->error("Error procesando el tenant ID {$tenant->id}: " . $e->getMessage());
                        Log::error("Error en cierre de asistencia [Tenant: {$tenant->id}]: " . $e->getMessage());
                    }
                }
            });

        $this->info('¡Proceso de cierre diario finalizado con éxito!');

        return self::SUCCESS;
    }

    private function resolveTargetDate(): ?string
    {
        $dateOption = $this->option('date');

        if ($dateOption === null || $dateOption === '') {
            return Carbon::today()->toDateString();
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/D', $dateOption) !== 1) {
            $this->error('La fecha debe tener el formato dd/mm/yy, por ejemplo 07/11/26.');

            return null;
        }

        $parsedDate = Carbon::createFromFormat('!d/m/y', $dateOption);
        $dateErrors = Carbon::getLastErrors();

        if (! $parsedDate || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            $this->error('La fecha indicada no es válida. Usa el formato dd/mm/yy, por ejemplo 07/11/26.');

            return null;
        }

        return $parsedDate->toDateString();
    }

    private function processTenant(Tenant $tenant, string $targetDate, SchedulePolicyResolver $policyResolver): void
    {
        $this->line("Procesando Tenant: {$tenant->id} - {$tenant->name}...");

        Employee::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('employment_status', 'active')
            ->chunkById(200, function ($employees) use ($targetDate, $policyResolver): void {
                foreach ($employees as $employee) {
                    $this->evaluateEmployee($employee, $targetDate, $policyResolver);
                }
            });
    }

    private function evaluateEmployee(Employee $employee, string $targetDate, SchedulePolicyResolver $policyResolver): void
    {
        $policy = $policyResolver->forEmployee($employee);
        $date = Carbon::parse($targetDate);
        $now = Carbon::now($policy->timezone($employee));

        $this->closeExpiredSessions($employee, $policy, $date, $now);
        $this->handleAbsence($employee, $policy, $date, $now);
    }

    private function closeExpiredSessions(
        Employee $employee,
        SchedulePolicy $policy,
        Carbon $date,
        Carbon $now,
    ): void {
        $openSessions = AttendanceSession::withoutGlobalScopes()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->whereNotIn('status', ['incomplete', 'absent'])
            ->get();

        foreach ($openSessions as $session) {
            $deadline = $policy->closingDeadline($employee, $date, $session);

            if ($deadline !== null && $now->gte($deadline)) {
                $session->update([
                    'status' => 'incomplete',
                    'check_out_at' => $session->check_out_at
                        ?? $policy->closingCheckOutAt($employee, $date, $session),
                ]);
            }
        }
    }

    private function handleAbsence(
        Employee $employee,
        SchedulePolicy $policy,
        Carbon $date,
        Carbon $now,
    ): void {
        if (! $policy->marksAbsenceOn($employee, $date, $now)) {
            return;
        }

        $targetDate = $date->toDateString();

        $session = AttendanceSession::withoutGlobalScopes()
            ->where('tenant_id', $employee->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $targetDate)
            ->first();

        if ($session?->check_in_at) {
            return;
        }

        $attributes = [
            'status' => 'absent',
            'scheduled_minutes' => $policy->scheduledMinutesFor($employee, $date),
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
        ];

        if ($session) {
            $session->update($attributes);

            return;
        }

        AttendanceSession::create(array_merge([
            'tenant_id' => $employee->tenant_id,
            'employee_id' => $employee->id,
            'attendance_date' => $targetDate,
        ], $attributes));
    }
}
