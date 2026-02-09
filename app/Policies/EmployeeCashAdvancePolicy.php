<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployeeCashAdvance;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeCashAdvancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployeeCashAdvance');
    }

    public function view(AuthUser $authUser, EmployeeCashAdvance $employeeCashAdvance): bool
    {
        return $authUser->can('View:EmployeeCashAdvance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployeeCashAdvance');
    }

    public function update(AuthUser $authUser, EmployeeCashAdvance $employeeCashAdvance): bool
    {
        return $authUser->can('Update:EmployeeCashAdvance');
    }

    public function delete(AuthUser $authUser, EmployeeCashAdvance $employeeCashAdvance): bool
    {
        return $authUser->can('Delete:EmployeeCashAdvance');
    }

    public function restore(AuthUser $authUser, EmployeeCashAdvance $employeeCashAdvance): bool
    {
        return $authUser->can('Restore:EmployeeCashAdvance');
    }

    public function forceDelete(AuthUser $authUser, EmployeeCashAdvance $employeeCashAdvance): bool
    {
        return $authUser->can('ForceDelete:EmployeeCashAdvance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmployeeCashAdvance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmployeeCashAdvance');
    }

    public function replicate(AuthUser $authUser, EmployeeCashAdvance $employeeCashAdvance): bool
    {
        return $authUser->can('Replicate:EmployeeCashAdvance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmployeeCashAdvance');
    }

}