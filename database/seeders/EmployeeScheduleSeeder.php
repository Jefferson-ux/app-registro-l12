<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants en la BD. Ejecuta TenantSeeder primero.');
            return;
        }

        $now = now()->toDateTimeString();
        $pastStart = now()->subYear()->startOfYear()->format('Y-m-d');
        $pastEnd = now()->subYear()->endOfYear()->format('Y-m-d');
        $currentStart = now()->startOfYear()->format('Y-m-d');

        $existingSchedules = EmployeeSchedule::whereIn('tenant_id', $tenants->pluck('id'))
            ->select('tenant_id', 'employee_id')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn($items) => $items->pluck('employee_id')->toArray())
            ->toArray();

        $schedulesToInsert = [];

        foreach ($tenants as $tenant) {
            $employees = Employee::where('tenant_id', $tenant->id)->get();
            $schedules = WorkSchedule::where('tenant_id', $tenant->id)->where('status', true)->get();

            if ($employees->isEmpty() || $schedules->isEmpty()) {
                continue;
            }

            $tenantExistingEmployees = $existingSchedules[$tenant->id] ?? [];

            foreach ($employees as $employee) {
                if (in_array($employee->id, $tenantExistingEmployees)) {
                    continue;
                }

                $currentSchedule = $schedules->random();

                if (rand(1, 100) <= 20 && $schedules->count() > 1) {
                    $pastSchedule = $schedules->where('id', '!=', $currentSchedule->id)->random() ?? $currentSchedule;

                    $schedulesToInsert[] = [
                        'tenant_id'        => $tenant->id,
                        'employee_id'      => $employee->id,
                        'work_schedule_id' => $pastSchedule->id,
                        'start_date'       => $pastStart,
                        'end_date'         => $pastEnd,
                        'status'           => false,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }

                $schedulesToInsert[] = [
                    'tenant_id'        => $tenant->id,
                    'employee_id'      => $employee->id,
                    'work_schedule_id' => $currentSchedule->id,
                    'start_date'       => $currentStart,
                    'end_date'         => null,
                    'status'           => true,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }

        if (! empty($schedulesToInsert)) {
            DB::transaction(function () use ($schedulesToInsert) {
                foreach (array_chunk($schedulesToInsert, 500) as $chunk) {
                    EmployeeSchedule::insert($chunk);
                }
            });
        }
    }
}