<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DevicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Device');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->can('View:Device');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Device');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->can('Update:Device');
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->can('Delete:Device');
    }
}
