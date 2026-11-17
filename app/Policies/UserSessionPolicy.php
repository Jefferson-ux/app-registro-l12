<?php

namespace App\Policies;

use App\Models\UserSession;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:UserSession');
    }

    public function view(AuthUser $authUser, UserSession $record): bool
    {
        return $authUser->can('View:UserSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return false; // las sesiones no se crean desde el panel
    }

    public function update(AuthUser $authUser, UserSession $record): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, UserSession $record): bool
    {
        return $authUser->can('Delete:UserSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:UserSession');
    }
}
