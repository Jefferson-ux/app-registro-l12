<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder; // Corregido: punto y coma agregado

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seeders de Datos de Dominio (Estructura base/necesaria)
        $this->call([
            PermissionsSeeder::class, // Debe ir primero para poder asignar roles en UserSeeder
            PlanSeeder::class,        // Los planes son datos maestros del sistema
        ]);

        // 2. Seeders de Datos Dummy (Solo para desarrollo/pruebas)
        if (app()->environment('local', 'testing')) {
            $this->call([
                TenantSeeder::class,
                UserSeeder::class,
                SubscriptionSeeder::class,
                AuditLogSeeder::class,
            ]);
        }
    }
}