<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\WorkScheduleDay;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkScheduleDayPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:WorkScheduleDay');
    }

    public function view(AuthUser $authUser, WorkScheduleDay $workScheduleDay): bool
    {
        return $authUser->can('View:WorkScheduleDay');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:WorkScheduleDay');
    }

    public function update(AuthUser $authUser, WorkScheduleDay $workScheduleDay): bool
    {
        return $authUser->can('Update:WorkScheduleDay');
    }

    public function delete(AuthUser $authUser, WorkScheduleDay $workScheduleDay): bool
    {
        return $authUser->can('Delete:WorkScheduleDay');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:WorkScheduleDay');
    }

    public function restore(AuthUser $authUser, WorkScheduleDay $workScheduleDay): bool
    {
        return $authUser->can('Restore:WorkScheduleDay');
    }

    public function forceDelete(AuthUser $authUser, WorkScheduleDay $workScheduleDay): bool
    {
        return $authUser->can('ForceDelete:WorkScheduleDay');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:WorkScheduleDay');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:WorkScheduleDay');
    }

    public function replicate(AuthUser $authUser, WorkScheduleDay $workScheduleDay): bool
    {
        return $authUser->can('Replicate:WorkScheduleDay');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:WorkScheduleDay');
    }

}