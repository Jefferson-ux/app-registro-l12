<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        $period = collect(Carbon::parse('-15 days')->daysUntil(now()))
            ->filter(fn(Carbon $date) => ! $date->isWeekend())
            ->map(fn(Carbon $date) => $date->format('Y-m-d'))
            ->values()
            ->toArray();

        $methods = ['web', 'mobile', 'kiosk', 'qr', 'manual', 'biometric', 'api'];
        $now = now()->toDateTimeString();

        foreach ($tenants as $tenant) {
            $employees = Employee::where('tenant_id', $tenant->id)->get();

            if ($employees->isEmpty()) {
                continue;
            }

            $employeeIds = $employees->pluck('id')->toArray();

            $existingRecords = AttendanceRecord::where('tenant_id', $tenant->id)
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('recorded_at', '>=', Carbon::parse('-15 days')->format('Y-m-d'))
                ->selectRaw('employee_id, DATE(recorded_at) as record_date')
                ->distinct()
                ->get()
                ->groupBy('employee_id')
                ->map(fn($items) => $items->pluck('record_date')->toArray())
                ->toArray();

            $recordsToInsert = [];

            foreach ($employees as $employee) {
                $employeeExistingDays = $existingRecords[$employee->id] ?? [];

                foreach ($period as $dateStr) {
                    if (in_array($dateStr, $employeeExistingDays)) {
                        continue;
                    }

                    $method = $methods[array_rand($methods)];
                    $deviceId = 'BIO-' . rand(100, 999);
                    $ip = '192.168.1.' . rand(10, 254);

                    $punches = [
                        ['type' => 'check_in',    'base_time' => "{$dateStr} 08:00:00"],
                        ['type' => 'break_start', 'base_time' => "{$dateStr} 13:00:00"],
                        ['type' => 'break_end',   'base_time' => "{$dateStr} 14:00:00"],
                        ['type' => 'check_out',   'base_time' => "{$dateStr} 17:00:00"],
                    ];

                    foreach ($punches as $punch) {
                        $recordedAt = Carbon::parse($punch['base_time'])->addMinutes(rand(-5, 15))->toDateTimeString();

                        $recordsToInsert[] = [
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
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ];
                    }
                }
            }

            if (! empty($recordsToInsert)) {
                DB::transaction(function () use ($recordsToInsert) {
                    foreach (array_chunk($recordsToInsert, 500) as $chunk) {
                        AttendanceRecord::insert($chunk);
                    }
                });
            }
        }
    }
}