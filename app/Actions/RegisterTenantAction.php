<?php

namespace App\Actions;

use App\Mail\ExampleMail;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterTenantAction
{
    public function __construct(
        private CreateTenantAdminRoleAction $createAdminRole,
        private AssignRoleToUserAction $assignRole,
    ) {}

    public function execute(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear el Tenant
            $tenant = Tenant::create([
                'name'          => $data['name'],
                'business_name' => $data['business_name'] ?? null,
                'tax_id'        => $data['tax_id'] ?? null,
                'email'         => $data['email'],
                'phone'         => $data['phone'] ?? null,
                'country'       => $data['country'] ?? null,
                'timezone'      => $data['timezone'] ?? null,
                'logo'           => $data['logo'] ?? null,
            ]);

            // 2. Generar usuario
            $username = $data['name'] . '-Admin';
            $plainPassword = Str::random(12);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $username,
                'email'     => $data['email'],
                'password'  => Hash::make($plainPassword),
            ]);

            $this->createAdminRole->execute($tenant->id);
            $this->assignRole->execute($user, 'admin');

            // 3. Enviar Correo
            Mail::to($user->email)
                ->send(new ExampleMail($user, $plainPassword));

            return $tenant;
        });
    }
}
