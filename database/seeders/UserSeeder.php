<?php

namespace Database\Seeders;

use App\Actions\AssignRoleToUserAction;
use App\Actions\CreateTenantAdminRoleAction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(
        CreateTenantAdminRoleAction $createTenantAdminRoleAction,
        AssignRoleToUserAction $assignRoleToUserAction
    ): void {
        // SuperAdmin (Sin Tenant)
        User::create([
            'name'      => 'SuperAdmin',
            'email'     => 'admin@gmail.com',
            'password'  => Hash::make('123456'),
            'status'    => 'active',
            'tenant_id' => null,
        ]);

        // Usuario Admin para Tenant 1
        $userTenant1 = User::create([
            'name'      => fake()->name(),
            'email'     => fake()->email(),
            'password'  => Hash::make('password_1'),
            'status'    => 'active',
            'tenant_id' => 1,
        ]);

        // Crear rol admin para Tenant 1 y asignárselo
        $createTenantAdminRoleAction->execute(1);
        $assignRoleToUserAction->execute($userTenant1, 'admin');

        // Usuario Admin para Tenant 2
        $userTenant2 = User::create([
            'name'      => fake()->name(),
            'email'     => fake()->email(),
            'password'  => Hash::make('password_2'),
            'status'    => 'inactive',
            'tenant_id' => 2,
        ]);

        // Crear rol admin para Tenant 2 y asignárselo
        $createTenantAdminRoleAction->execute(2);
        $assignRoleToUserAction->execute($userTenant2, 'admin');

        // Demás usuarios regulares
        User::create([
            'name'      => fake()->name(),
            'email'     => fake()->email(),
            'password'  => Hash::make('password_3'),
            'status'    => 'active',
            'tenant_id' => 3,
        ]);

        User::create([
            'name'      => fake()->name(),
            'email'     => fake()->email(),
            'password'  => Hash::make('password_4'),
            'status'    => 'active',
            'tenant_id' => 4,
        ]);

        User::create([
            'name'     => fake()->name(),
            'email'    => fake()->email(),
            'password' => Hash::make('password_5'),
            'status'   => 'blocked',
        ]);
    }
}