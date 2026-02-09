<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployeeAttendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeAttendancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployeeAttendance');
    }

    public function view(AuthUser $authUser, EmployeeAttendance $employeeAttendance): bool
    {
        return $authUser->can('View:EmployeeAttendance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployeeAttendance');
    }

    public function update(AuthUser $authUser, EmployeeAttendance $employeeAttendance): bool
    {
        return $authUser->can('Update:EmployeeAttendance');
    }

    public function delete(AuthUser $authUser, EmployeeAttendance $employeeAttendance): bool
    {
        return $authUser->can('Delete:EmployeeAttendance');
    }

    public function restore(AuthUser $authUser, EmployeeAttendance $employeeAttendance): bool
    {
        return $authUser->can('Restore:EmployeeAttendance');
    }

    public function forceDelete(AuthUser $authUser, EmployeeAttendance $employeeAttendance): bool
    {
        return $authUser->can('ForceDelete:EmployeeAttendance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmployeeAttendance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmployeeAttendance');
    }

    public function replicate(AuthUser $authUser, EmployeeAttendance $employeeAttendance): bool
    {
        return $authUser->can('Replicate:EmployeeAttendance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmployeeAttendance');
    }

}