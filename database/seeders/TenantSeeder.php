<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{

    public function run(): void
    {
        Tenant::create([
            'uuid' => fake()->uuid(),
            'name' => "Empresa 01",
            'business_name' => "Empresa 01 S.A.",
            'tax_id' => "123456789",
            'email' => "info@empresa01.com",
            'phone' => "+51 903212009",
            'country' => "Peru",
            'timezone' => "America/Peru",
            'logo' => "logo-empresa01.png",
            'status' => "trial",
            'trial_ends_at' => now()->addDays(14),
        ]);
        Tenant::create([
            'uuid' => fake()->uuid(),
            'name' => "Empresa 02",
            'business_name' => "Empresa 02 S.A.C",
            'tax_id' => "0021341481",
            'email' => "info@empresa02.com",
            'phone' => "+52 39929384",
            'country' => "Mexico",
            'timezone' => "America/Mexico_City",
            'logo' => "logo-empresa02.png",
            'status' => "active"
        ]);
        Tenant::create([
            'uuid' => fake()->uuid(),
            'name' => "Empresa 03",
            'business_name' => "Empresa 03 S.A.",
            'tax_id' => "2223334445",
            'email' => "info@empresa03.com",
            'phone' => "+51 979321456",
            'country' => "Peru",
            'timezone' => "America/Peru",
            'logo' => "logo-empresa03.png",
            'status' => "active",
        ]);
    }
}
