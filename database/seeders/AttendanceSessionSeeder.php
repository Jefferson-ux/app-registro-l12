<?php

namespace Database\Seeders;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSessionSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        // Últimos 15 días laborables (Lunes a Viernes) como array de strings 'Y-m-d'
        $period = collect(Carbon::parse('-15 days')->daysUntil(now()))
            ->filter(fn(Carbon $date) => ! $date->isWeekend())
            ->map(fn(Carbon $date) => $date->format('Y-m-d'))
            ->values();

        if ($period->isEmpty()) {
            return;
        }

        $now = now()->toDateTimeString();

        // Cargar todas las sesiones existentes de este período en memoria RAM [employee_id|date => true]
        $existingSessions = AttendanceSession::whereIn('tenant_id', $tenants->pluck('id'))
            ->whereIn('attendance_date', $period)
            ->select('employee_id', 'attendance_date')
            ->get()
            ->mapWithKeys(fn($item) => ["{$item->employee_id}|{$item->attendance_date}" => true])
            ->toArray();

        $sessionsToInsert = [];

        foreach ($tenants as $tenant) {
            $employees = Employee::where('tenant_id', $tenant->id)->pluck('id');

            if ($employees->isEmpty()) {
                continue;
            }

            foreach ($employees as $employeeId) {
                foreach ($period as $dateStr) {
                    $key = "{$employeeId}|{$dateStr}";

                    if (isset($existingSessions[$key])) {
                        continue;
                    }

                    $rand = rand(1, 100);

                    if ($rand <= 75) {
                        // 75% Presente
                        $status = 'present';
                        $checkIn = "{$dateStr} 08:00:00";
                        $checkOut = "{$dateStr} 17:00:00";
                        $workedMinutes = 480;
                        $lateMinutes = 0;
                    } elseif ($rand <= 87) {
                        // 12% Tardanza
                        $status = 'late';
                        $lateMinutes = rand(10, 40);
                        $checkIn = "{$dateStr} 08:" . str_pad((string) $lateMinutes, 2, '0', STR_PAD_LEFT) . ":00";
                        $checkOut = "{$dateStr} 17:00:00";
                        $workedMinutes = 480 - $lateMinutes;
                    } elseif ($rand <= 92) {
                        // 5% Incompleto (sin marcaje de salida)
                        $status = 'incomplete';
                        $lateMinutes = 0;
                        $checkIn = "{$dateStr} 08:00:00";
                        $checkOut = null;
                        $workedMinutes = 240;
                    } elseif ($rand <= 96) {
                        // 4% Licencia / Permiso
                        $status = 'leave';
                        $lateMinutes = 0;
                        $checkIn = null;
                        $checkOut = null;
                        $workedMinutes = 0;
                    } else {
                        // 4% Falta / Ausente
                        $status = 'absent';
                        $lateMinutes = 0;
                        $checkIn = null;
                        $checkOut = null;
                        $workedMinutes = 0;
                    }

                    $sessionsToInsert[] = [
                        'tenant_id'           => $tenant->id,
                        'employee_id'         => $employeeId,
                        'attendance_date'     => $dateStr,
                        'check_in_at'         => $checkIn,
                        'check_out_at'        => $checkOut,
                        'scheduled_minutes'   => 480,
                        'worked_minutes'      => $workedMinutes,
                        'late_minutes'        => $lateMinutes,
                        'early_leave_minutes' => 0,
                        'overtime_minutes'    => 0,
                        'status'              => $status,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }
            }
        }

        if (! empty($sessionsToInsert)) {
            DB::transaction(function () use ($sessionsToInsert) {
                foreach (array_chunk($sessionsToInsert, 500) as $chunk) {
                    AttendanceSession::insert($chunk);
                }
            });
        }
    }
}