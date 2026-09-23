<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SubscriptionSeeder extends Seeder
{

    public function run(): void
    {
        // 1. Obtener la colección de IDs de planes disponibles (se pueden repetir entre suscripciones)
        $plans = Plan::pluck('id');

        // 2. Obtener solo los usuarios que pertenecen a un Tenant (excluye SuperAdmins huérfanos)
        $usersWithTenant = User::whereNotNull('tenant_id')->get();

        // 3. Crear las suscripciones basadas en el tenant_id real de cada usuario
        foreach ($usersWithTenant as $user) {

            Subscription::create([
                'tenant_id'    => $user->tenant_id,
                'plan_id'      => $plans->random(), 
                'status'       => fake()->randomElement(['active', 'expired', 'cancelled', 'trial']),
                'starts_at'    => now()->subMonths(rand(1, 6)),
                'ends_at'      => now()->addMonths(rand(1, 12)),
                'cancelled_at' => null,
            ]);
        }
    }
}
