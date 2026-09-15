<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployeeSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeSchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployeeSchedule');
    }

    public function view(AuthUser $authUser, EmployeeSchedule $employeeSchedule): bool
    {
        return $authUser->can('View:EmployeeSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployeeSchedule');
    }

    public function update(AuthUser $authUser, EmployeeSchedule $employeeSchedule): bool
    {
        return $authUser->can('Update:EmployeeSchedule');
    }

    public function delete(AuthUser $authUser, EmployeeSchedule $employeeSchedule): bool
    {
        return $authUser->can('Delete:EmployeeSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EmployeeSchedule');
    }

    public function restore(AuthUser $authUser, EmployeeSchedule $employeeSchedule): bool
    {
        return $authUser->can('Restore:EmployeeSchedule');
    }

    public function forceDelete(AuthUser $authUser, EmployeeSchedule $employeeSchedule): bool
    {
        return $authUser->can('ForceDelete:EmployeeSchedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmployeeSchedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmployeeSchedule');
    }

    public function replicate(AuthUser $authUser, EmployeeSchedule $employeeSchedule): bool
    {
        return $authUser->can('Replicate:EmployeeSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmployeeSchedule');
    }

}