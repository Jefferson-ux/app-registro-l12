<?php

namespace Database\Seeders;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendanceSessionSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        // Últimos 15 días laborables (Lunes a Viernes)
        $period = collect(Carbon::parse('-15 days')->daysUntil(now()))
            ->filter(fn(Carbon $date) => ! $date->isWeekend());

        foreach ($tenants as $tenant) {
            $employees = Employee::where('tenant_id', $tenant->id)->get();

            if ($employees->isEmpty()) {
                continue;
            }

            foreach ($employees as $employee) {
                foreach ($period as $date) {
                    $dateStr = $date->format('Y-m-d');

                    if (AttendanceSession::where('tenant_id', $tenant->id)
                        ->where('employee_id', $employee->id)
                        ->where('attendance_date', $dateStr)
                        ->exists()
                    ) {
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
                        $checkIn = Carbon::parse("{$dateStr} 08:00:00")->addMinutes($lateMinutes)->toDateTimeString();
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

                    AttendanceSession::create([
                        'tenant_id'           => $tenant->id,
                        'employee_id'         => $employee->id,
                        'attendance_date'     => $dateStr,
                        'check_in_at'         => $checkIn,
                        'check_out_at'        => $checkOut,
                        'scheduled_minutes'   => 480,
                        'worked_minutes'      => $workedMinutes,
                        'late_minutes'        => $lateMinutes,
                        'early_leave_minutes' => 0,
                        'overtime_minutes'    => 0,
                        'status'              => $status,
                    ]);
                }
            }
        }
    }
}
