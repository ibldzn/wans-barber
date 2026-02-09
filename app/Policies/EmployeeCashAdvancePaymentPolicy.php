<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EmployeeCashAdvancePayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeeCashAdvancePaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmployeeCashAdvancePayment');
    }

    public function view(AuthUser $authUser, EmployeeCashAdvancePayment $employeeCashAdvancePayment): bool
    {
        return $authUser->can('View:EmployeeCashAdvancePayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmployeeCashAdvancePayment');
    }

    public function update(AuthUser $authUser, EmployeeCashAdvancePayment $employeeCashAdvancePayment): bool
    {
        return $authUser->can('Update:EmployeeCashAdvancePayment');
    }

    public function delete(AuthUser $authUser, EmployeeCashAdvancePayment $employeeCashAdvancePayment): bool
    {
        return $authUser->can('Delete:EmployeeCashAdvancePayment');
    }

    public function restore(AuthUser $authUser, EmployeeCashAdvancePayment $employeeCashAdvancePayment): bool
    {
        return $authUser->can('Restore:EmployeeCashAdvancePayment');
    }

    public function forceDelete(AuthUser $authUser, EmployeeCashAdvancePayment $employeeCashAdvancePayment): bool
    {
        return $authUser->can('ForceDelete:EmployeeCashAdvancePayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmployeeCashAdvancePayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmployeeCashAdvancePayment');
    }

    public function replicate(AuthUser $authUser, EmployeeCashAdvancePayment $employeeCashAdvancePayment): bool
    {
        return $authUser->can('Replicate:EmployeeCashAdvancePayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmployeeCashAdvancePayment');
    }

}