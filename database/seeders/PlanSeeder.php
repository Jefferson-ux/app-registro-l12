<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planes = [
            [
                'name' => 'Plan Gratis (Trial)',
                'slug' => Str::slug('Plan Gratis Trial'),
                'description' => 'Ideal para probar la plataforma con funciones básicas.',
                'max_employees' => 5,
                'max_users' => 2,
                'max_branches' => 1,
                'price' => 0.00,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'status' => 1,
            ],
            [
                'name' => 'Plan Emprendedor',
                'slug' => Str::slug('Plan Emprendedor'),
                'description' => 'Perfecto para pequeñas empresas en crecimiento.',
                'max_employees' => 20,
                'max_users' => 5,
                'max_branches' => 2,
                'price' => 19.90,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'status' => 1,
            ],
            [
                'name' => 'Plan Profesional Mensual',
                'slug' => Str::slug('Plan Profesional Mensual'),
                'description' => 'Gestión completa para medianas empresas.',
                'max_employees' => 100,
                'max_users' => 15,
                'max_branches' => 5,
                'price' => 49.90,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'status' => 1,
            ],
            [
                'name' => 'Plan Profesional Anual',
                'slug' => Str::slug('Plan Profesional Anual'),
                'description' => 'Mismas funciones profesionales con un ahorro del 20%.',
                'max_employees' => 100,
                'max_users' => 15,
                'max_branches' => 5,
                'price' => 479.00,
                'currency' => 'USD',
                'billing_period' => 'yearly',
                'status' => 1,
            ],
            [
                'name' => 'Plan Corporativo',
                'slug' => Str::slug('Plan Corporativo'),
                'description' => 'Capacidad masiva para empresas grandes con múltiples sucursales.',
                'max_employees' => 500,
                'max_users' => 50,
                'max_branches' => 20,
                'price' => 149.90,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'status' => 1,
            ],
            [
                'name' => 'Plan Corporativo Anual',
                'slug' => Str::slug('Plan Corporativo Anual'),
                'description' => 'Capacidades masivas con un descuento de 10%.',
                'max_employees' => 500,
                'max_users' => 50,
                'max_branches' => 20,
                'price' => 1610.90,
                'currency' => 'USD',
                'billing_period' => 'yearly',
                'status' => 1,
            ],
        ];

        // Recorremos el arreglo e insertamos cada plan en la base de datos
        foreach ($planes as $plan) {
            Plan::create($plan);
        }
    }
}
