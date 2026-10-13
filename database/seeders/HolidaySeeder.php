<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        $peruvianHolidays = [
            ['name' => 'Año Nuevo', 'date' => '2026-01-01'],
            ['name' => 'Jueves Santo', 'date' => '2026-04-02'],
            ['name' => 'Viernes Santo', 'date' => '2026-04-03'],
            ['name' => 'Día del Trabajador', 'date' => '2026-05-01'],
            ['name' => 'San Pedro y San Pablo', 'date' => '2026-06-29'],
            ['name' => 'Fiestas Patrias', 'date' => '2026-07-28'],
            ['name' => 'Fiestas Patrias', 'date' => '2026-07-29'],
            ['name' => 'Santa Rosa de Lima', 'date' => '2026-08-30'],
            ['name' => 'Combate de Angamos', 'date' => '2026-10-08'],
            ['name' => 'Todos los Santos', 'date' => '2026-11-01'],
            ['name' => 'Inmaculada Concepción', 'date' => '2026-12-08'],
            ['name' => 'Navidad del Señor', 'date' => '2026-12-25'],
        ];

        $now = now()->toDateTimeString();

        $existingHolidays = Holiday::whereIn('tenant_id', $tenants->pluck('id'))
            ->select('tenant_id', 'holiday_date', 'name')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn($items) => $items->map(fn($item) => $item->holiday_date . '|' . $item->name)->toArray())
            ->toArray();

        $holidaysToInsert = [];

        foreach ($tenants as $tenant) {
            $tenantExisting = $existingHolidays[$tenant->id] ?? [];

            foreach ($peruvianHolidays as $holiday) {
                $key = $holiday['date'] . '|' . $holiday['name'];

                if (in_array($key, $tenantExisting)) {
                    continue;
                }

                $holidaysToInsert[] = [
                    'tenant_id'    => $tenant->id,
                    'branch_id'    => null,
                    'holiday_date' => $holiday['date'],
                    'name'         => $holiday['name'],
                    'is_paid'      => true,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        if (! empty($holidaysToInsert)) {
            DB::transaction(function () use ($holidaysToInsert) {
                foreach (array_chunk($holidaysToInsert, 500) as $chunk) {
                    Holiday::insert($chunk);
                }
            });
        }
    }
}