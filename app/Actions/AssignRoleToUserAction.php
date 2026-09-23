<?php

namespace App\Actions;

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class AssignRoleToUserAction
{
    public function execute(User $user, string $roleName): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $user->assignRole($roleName);
    }
}