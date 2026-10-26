<?php

namespace App\Actions;

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class AssignRoleToUserAction
{
    public function execute(User $user, string $roleName): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($user->tenant_id);
            $user->assignRole($roleName);
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}