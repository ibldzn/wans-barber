<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProductConsumable;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductConsumablePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductConsumable');
    }

    public function view(AuthUser $authUser, ProductConsumable $productConsumable): bool
    {
        return $authUser->can('View:ProductConsumable');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductConsumable');
    }

    public function update(AuthUser $authUser, ProductConsumable $productConsumable): bool
    {
        return $authUser->can('Update:ProductConsumable');
    }

    public function delete(AuthUser $authUser, ProductConsumable $productConsumable): bool
    {
        return $authUser->can('Delete:ProductConsumable');
    }

    public function restore(AuthUser $authUser, ProductConsumable $productConsumable): bool
    {
        return $authUser->can('Restore:ProductConsumable');
    }

    public function forceDelete(AuthUser $authUser, ProductConsumable $productConsumable): bool
    {
        return $authUser->can('ForceDelete:ProductConsumable');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductConsumable');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductConsumable');
    }

    public function replicate(AuthUser $authUser, ProductConsumable $productConsumable): bool
    {
        return $authUser->can('Replicate:ProductConsumable');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductConsumable');
    }

}