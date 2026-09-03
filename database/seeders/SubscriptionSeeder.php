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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // -------------------------------------------------------------
        // 1. OBTENER LOS PLANES (De los slugs creados en tu PlanSeeder)
        // -------------------------------------------------------------
        $pTrial        = Plan::where('slug', 'plan-gratis-trial')->first()?->id ?? 1;
        $pEmprendedor  = Plan::where('slug', 'plan-emprendedor')->first()?->id ?? 2;
        $pProMensual   = Plan::where('slug', 'plan-profesional-mensual')->first()?->id ?? 3;
        $pProAnual     = Plan::where('slug', 'plan-profesional-anual')->first()?->id ?? 4;
        $pCorpMensual  = Plan::where('slug', 'plan-corporativo')->first()?->id ?? 5;
        $pCorpAnual    = Plan::where('slug', 'plan-corporativo-anual')->first()?->id ?? 6;

        // -------------------------------------------------------------
        // 2. CREAR LOS 6 TENANTS A MANO (Nombres limpios en minúsculas)
        // -------------------------------------------------------------
        $tenants = [];
        for ($i = 1; $i <= 6; $i++) {
            $tenants[$i] = Tenant::create([
                'uuid' => (string) Str::uuid(),
                'name' => "empresa-0{$i}",
                'business_name' => "Empresa Contable 0{$i} S.A.C.",
                'tax_id' => "2012345678{$i}",
                'email' => "contacto@empresa0{$i}.com",
                'phone' => "+51 90000000{$i}",
                'country' => 'Peru',
                'timezone' => 'America/Lima',
                'status' => $i === 5 ? 'inactive' : 'active',
            ]);
        }

        // -------------------------------------------------------------
        // 3. CREAR LOS 5 USUARIOS (User 5 se queda con tenant_id = null)
        // -------------------------------------------------------------
        for ($u = 1; $u <= 4; $u++) {
            User::create([
                'name' => "Usuario 0{$u}",
                'email' => "user0{$u}@gmail.com",
                'password' => Hash::make('password123'),
                'tenant_id' => $tenants[$u]->id, // Vinculados a sus respectivos tenants
            ]);
        }

        // 🔥 EL USUARIO 5: Huérfano absoluto (Sin tenant_id asignado)
        User::create([
            'name' => "Usuario 05 (Super Admin)",
            'email' => "superadmin@gmail.com",
            'password' => Hash::make('password123'),
            'tenant_id' => null, // 100% libre de estructura tenant
        ]);

        // -------------------------------------------------------------
        // 4. INYECTAR LAS 14 SUSCRIPCIONES (Flujo cronológico estricto)
        // -------------------------------------------------------------
        $suscripciones = [
            // 🏢 TENANT 1: Cliente leal (Tuvo trial, pasó a mensual y ahora está en Plan Anual Activo)
            [
                'tenant_id' => $tenants[1]->id, 'plan_id' => $pTrial, 'status' => 'expired',
                'starts_at' => '2026-01-01 08:00:00', 'ends_at' => '2026-01-15 08:00:00', 'cancelled_at' => null
            ],
            [
                'tenant_id' => $tenants[1]->id, 'plan_id' => $pEmprendedor, 'status' => 'expired',
                'starts_at' => '2026-01-15 08:00:00', 'ends_at' => '2026-02-15 08:00:00', 'cancelled_at' => null
            ],
            [
                'tenant_id' => $tenants[1]->id, 'plan_id' => $pProAnual, 'status' => 'active',
                'starts_at' => '2026-02-15 08:00:00', 'ends_at' => '2027-02-15 08:00:00', 'cancelled_at' => null
            ],

            // 🏢 TENANT 2: Cliente estándar (Trial expirado, actualmente en Plan Profesional Activo)
            [
                'tenant_id' => $tenants[2]->id, 'plan_id' => $pTrial, 'status' => 'expired',
                'starts_at' => '2026-03-01 10:00:00', 'ends_at' => '2026-03-15 10:00:00', 'cancelled_at' => null
            ],
            [
                'tenant_id' => $tenants[2]->id, 'plan_id' => $pProMensual, 'status' => 'active',
                'starts_at' => '2026-08-10 00:00:00', 'ends_at' => '2026-09-10 00:00:00', 'cancelled_at' => null
            ],

            // 🏢 TENANT 3: Cliente arrepentido (Entró directo a Corporativo, pero canceló a mitad de mes)
            [
                'tenant_id' => $tenants[3]->id, 'plan_id' => $pCorpMensual, 'status' => 'cancelled',
                'starts_at' => '2026-07-01 00:00:00', 'ends_at' => '2026-08-01 00:00:00', 
                'cancelled_at' => '2026-07-18 14:30:00' // Se registra el día que tiró la baja
            ],

            // 🏢 TENANT 4: Cliente corporativo pesado (Doble historial expirado y un Corporativo Anual Activo)
            [
                'tenant_id' => $tenants[4]->id, 'plan_id' => $pProMensual, 'status' => 'expired',
                'starts_at' => '2026-02-01 09:00:00', 'ends_at' => '2026-03-01 09:00:00', 'cancelled_at' => null
            ],
            [
                'tenant_id' => $tenants[4]->id, 'plan_id' => $pCorpMensual, 'status' => 'expired',
                'starts_at' => '2026-03-01 09:00:00', 'ends_at' => '2026-04-01 09:00:00', 'cancelled_at' => null
            ],
            [
                'tenant_id' => $tenants[4]->id, 'plan_id' => $pCorpAnual, 'status' => 'active',
                'starts_at' => '2026-04-01 09:00:00', 'ends_at' => '2027-04-01 09:00:00', 'cancelled_at' => null
            ],

            // 🏢 TENANT 5: Cliente dado de baja (Su trial expiró y se marchó del SaaS)
            [
                'tenant_id' => $tenants[5]->id, 'plan_id' => $pTrial, 'status' => 'expired',
                'starts_at' => '2026-01-10 12:00:00', 'ends_at' => '2026-01-24 12:00:00', 'cancelled_at' => null
            ],

            // 🏢 TENANT 6: Clientes en periodo de evaluación (Suscripciones de tipo "trial" activas o muertas)
            [
                'tenant_id' => $tenants[6]->id, 'plan_id' => $pTrial, 'status' => 'active',
                'starts_at' => '2026-08-25 00:00:00', 'ends_at' => '2026-09-08 00:00:00', 'cancelled_at' => null
            ],
            // Suscripciones huérfanas o históricas adicionales para completar el bloque de 14 estrictas
            [
                'tenant_id' => $tenants[2]->id, 'plan_id' => $pEmprendedor, 'status' => 'expired',
                'starts_at' => '2026-04-01 00:00:00', 'ends_at' => '2026-05-01 00:00:00', 'cancelled_at' => null
            ],
            [
                'tenant_id' => $tenants[3]->id, 'plan_id' => $pTrial, 'status' => 'expired',
                'starts_at' => '2026-05-01 00:00:00', 'ends_at' => '2026-05-15 00:00:00', 'cancelled_at' => null
            ],
            [
                'tenant_id' => $tenants[6]->id, 'plan_id' => $pEmprendedor, 'status' => 'cancelled',
                'starts_at' => '2026-06-01 00:00:00', 'ends_at' => '2026-07-01 00:00:00', 'cancelled_at' => '2026-06-10 11:00:00'
            ],
        ];

        // Guardamos todo de un solo golpe en la base de datos
        foreach ($suscripciones as $sub) {
            Subscription::create($sub);
        }
    }
}
