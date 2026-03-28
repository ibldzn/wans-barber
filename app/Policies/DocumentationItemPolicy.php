<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentationItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentationItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DocumentationItem');
    }

    public function view(AuthUser $authUser, DocumentationItem $documentationItem): bool
    {
        return $authUser->can('View:DocumentationItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DocumentationItem');
    }

    public function update(AuthUser $authUser, DocumentationItem $documentationItem): bool
    {
        return $authUser->can('Update:DocumentationItem');
    }

    public function delete(AuthUser $authUser, DocumentationItem $documentationItem): bool
    {
        return $authUser->can('Delete:DocumentationItem');
    }

    public function restore(AuthUser $authUser, DocumentationItem $documentationItem): bool
    {
        return $authUser->can('Restore:DocumentationItem');
    }

    public function forceDelete(AuthUser $authUser, DocumentationItem $documentationItem): bool
    {
        return $authUser->can('ForceDelete:DocumentationItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DocumentationItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DocumentationItem');
    }

    public function replicate(AuthUser $authUser, DocumentationItem $documentationItem): bool
    {
        return $authUser->can('Replicate:DocumentationItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DocumentationItem');
    }
}
