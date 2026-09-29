<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceRecordSeeder extends Seeder
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

        $methods = ['web', 'mobile', 'kiosk', 'qr', 'manual', 'biometric', 'api'];

        foreach ($tenants as $tenant) {
            $employees = Employee::where('tenant_id', $tenant->id)->get();

            if ($employees->isEmpty()) {
                continue;
            }

            foreach ($employees as $employee) {
                foreach ($period as $date) {
                    $dateStr = $date->format('Y-m-d');

                    // Evitar duplicar registros para la misma fecha y empleado
                    if (AttendanceRecord::where('tenant_id', $tenant->id)
                        ->where('employee_id', $employee->id)
                        ->whereDate('recorded_at', $dateStr)
                        ->exists()
                    ) {
                        continue;
                    }

                    $method = $methods[array_rand($methods)];
                    $deviceId = 'BIO-' . rand(100, 999);
                    $ip = '192.168.1.' . rand(10, 254);

                    // Secuencia diaria completa de marcajes crudos
                    $punches = [
                        ['type' => 'check_in',   'base_time' => "{$dateStr} 08:00:00"],
                        ['type' => 'break_start', 'base_time' => "{$dateStr} 13:00:00"],
                        ['type' => 'break_end',   'base_time' => "{$dateStr} 14:00:00"],
                        ['type' => 'check_out',  'base_time' => "{$dateStr} 17:00:00"],
                    ];

                    foreach ($punches as $punch) {
                        // Variación de minutos aleatoria (-5 a +15 min) sobre la hora base
                        $recordedAt = Carbon::parse($punch['base_time'])->addMinutes(rand(-5, 15));

                        AttendanceRecord::create([
                            'tenant_id'         => $tenant->id,
                            'employee_id'       => $employee->id,
                            'branch_id'         => $employee->branch_id,
                            'type'              => $punch['type'],
                            'recorded_at'       => $recordedAt,
                            'method'            => $method,
                            'latitude'          => in_array($method, ['mobile', 'kiosk']) ? fake()->latitude() : null,
                            'longitude'         => in_array($method, ['mobile', 'kiosk']) ? fake()->longitude() : null,
                            'ip_address'        => $ip,
                            'device_identifier' => $deviceId,
                            'notes'             => null,
                        ]);
                    }
                }
            }
        }
    }
}
