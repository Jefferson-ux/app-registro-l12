<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WorkSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkSchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WorkSchedule');
    }

    public function view(AuthUser $authUser, WorkSchedule $workSchedule): bool
    {
        return $authUser->can('View:WorkSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WorkSchedule');
    }

    public function update(AuthUser $authUser, WorkSchedule $workSchedule): bool
    {
        return $authUser->can('Update:WorkSchedule');
    }

    public function delete(AuthUser $authUser, WorkSchedule $workSchedule): bool
    {
        return $authUser->can('Delete:WorkSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WorkSchedule');
    }

    public function restore(AuthUser $authUser, WorkSchedule $workSchedule): bool
    {
        return $authUser->can('Restore:WorkSchedule');
    }

    public function forceDelete(AuthUser $authUser, WorkSchedule $workSchedule): bool
    {
        return $authUser->can('ForceDelete:WorkSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WorkSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WorkSchedule');
    }

    public function replicate(AuthUser $authUser, WorkSchedule $workSchedule): bool
    {
        return $authUser->can('Replicate:WorkSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WorkSchedule');
    }

}