<?php

namespace Database\Seeders;

use Database\Seeders\Production\{PermissionsSeeder, PlanSeeder};
use Database\Seeders\Testing\EmployeesAndSchedulesSeeder;
use Database\Seeders\Testing\OrganizationSeeder;
use Database\Seeders\Testing\SchedulesSeeder;
use Database\Seeders\Testing\TenantAndUserSeeder;
use Illuminate\Database\Seeder;

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
                TenantAndUserSeeder::class,
                OrganizationSeeder::class,
                SchedulesSeeder::class,
                EmployeesAndSchedulesSeeder::class,
            ]);
        }
    }
}
