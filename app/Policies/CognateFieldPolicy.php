<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CognateField;
use Illuminate\Auth\Access\HandlesAuthorization;

class CognateFieldPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CognateField');
    }

    public function view(AuthUser $authUser, CognateField $cognateField): bool
    {
        return $authUser->can('View:CognateField');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CognateField');
    }

    public function update(AuthUser $authUser, CognateField $cognateField): bool
    {
        return $authUser->can('Update:CognateField');
    }

    public function delete(AuthUser $authUser, CognateField $cognateField): bool
    {
        return $authUser->can('Delete:CognateField');
    }

    public function restore(AuthUser $authUser, CognateField $cognateField): bool
    {
        return $authUser->can('Restore:CognateField');
    }

    public function forceDelete(AuthUser $authUser, CognateField $cognateField): bool
    {
        return $authUser->can('ForceDelete:CognateField');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CognateField');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CognateField');
    }

    public function replicate(AuthUser $authUser, CognateField $cognateField): bool
    {
        return $authUser->can('Replicate:CognateField');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CognateField');
    }

}