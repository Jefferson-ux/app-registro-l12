<?php

namespace App\Actions;

use App\Mail\RegisterMail;
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
        private CreateDefaultWorkScheduleAction $createDefaultSchedule,
    ) {}

public function execute(array $data): Tenant
{
    [$tenant, $user, $plainPassword] = DB::transaction(function () use ($data) {
        $tenant = Tenant::create([
            'name'          => $data['name'],
            'business_name' => $data['business_name'] ?? null,
            'tax_id'        => $data['tax_id'] ?? null,
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'country'       => $data['country'] ?? null,
            'timezone'      => $data['timezone'] ?? null,
            'logo'          => $data['logo'] ?? null,
        ]);

        $plainPassword = Str::random(12);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($plainPassword),
        ]);

        $this->createAdminRole->execute($tenant->id);
        $this->assignRole->execute($user, 'admin');
        $this->createDefaultSchedule->execute($tenant->id);

        return [$tenant, $user, $plainPassword];
    });
    
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    Mail::to($user->email)->send(new RegisterMail($user, $plainPassword));

    return $tenant;
}
}
