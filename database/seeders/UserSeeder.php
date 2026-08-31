<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => Hash::make('contraseña_1'),
            'status' => 'active',
            'tenant_id' => 1
        ]);
        User::create([
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => Hash::make('contraseña_2'),
            'status' => 'inactive',
        ]);
    }
}
