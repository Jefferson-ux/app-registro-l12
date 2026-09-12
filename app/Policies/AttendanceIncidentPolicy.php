<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AttendanceIncident;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceIncidentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AttendanceIncident');
    }

    public function view(AuthUser $authUser, AttendanceIncident $attendanceIncident): bool
    {
        return $authUser->can('View:AttendanceIncident');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AttendanceIncident');
    }

    public function update(AuthUser $authUser, AttendanceIncident $attendanceIncident): bool
    {
        return $authUser->can('Update:AttendanceIncident');
    }

    public function delete(AuthUser $authUser, AttendanceIncident $attendanceIncident): bool
    {
        return $authUser->can('Delete:AttendanceIncident');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AttendanceIncident');
    }

    public function restore(AuthUser $authUser, AttendanceIncident $attendanceIncident): bool
    {
        return $authUser->can('Restore:AttendanceIncident');
    }

    public function forceDelete(AuthUser $authUser, AttendanceIncident $attendanceIncident): bool
    {
        return $authUser->can('ForceDelete:AttendanceIncident');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AttendanceIncident');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AttendanceIncident');
    }

    public function replicate(AuthUser $authUser, AttendanceIncident $attendanceIncident): bool
    {
        return $authUser->can('Replicate:AttendanceIncident');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AttendanceIncident');
    }

}