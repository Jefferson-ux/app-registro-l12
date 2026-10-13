<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        // 1. Obtener IDs necesarias en memoria en solo 2 consultas ligeras
        $planIds   = Plan::pluck('id');
        $tenantIds = Tenant::pluck('id');

        if ($planIds->isEmpty() || $tenantIds->isEmpty()) {
            return;
        }

        $statuses = ['active', 'expired', 'cancelled', 'trial'];
        $now = now()->toDateTimeString();
        $subscriptionsToInsert = [];

        // 2. Iterar sobre los Tenants (1 sola suscripción por empresa)
        foreach ($tenantIds as $tenantId) {
            $status   = $statuses[array_rand($statuses)];
            $startsAt = now()->subMonths(rand(1, 6));

            $subscriptionsToInsert[] = [
                'tenant_id'    => $tenantId,
                'plan_id'      => $planIds->random(),
                'status'       => $status,
                'starts_at'    => $startsAt->toDateTimeString(),
                'ends_at'      => (clone $startsAt)->addMonths(rand(1, 12))->toDateTimeString(),
                'cancelled_at' => $status === 'cancelled' ? now()->subDays(rand(1, 15))->toDateTimeString() : null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        // 3. Inserción masiva global
        if (! empty($subscriptionsToInsert)) {
            DB::transaction(function () use ($subscriptionsToInsert) {
                foreach (array_chunk($subscriptionsToInsert, 500) as $chunk) {
                    Subscription::insert($chunk);
                }
            });
        }
    }
}