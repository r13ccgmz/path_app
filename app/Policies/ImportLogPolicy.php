<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ImportLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ImportLog');
    }

    public function view(AuthUser $authUser, ImportLog $importLog): bool
    {
        return $authUser->can('View:ImportLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ImportLog');
    }

    public function update(AuthUser $authUser, ImportLog $importLog): bool
    {
        return $authUser->can('Update:ImportLog');
    }

    public function delete(AuthUser $authUser, ImportLog $importLog): bool
    {
        return $authUser->can('Delete:ImportLog');
    }

    public function restore(AuthUser $authUser, ImportLog $importLog): bool
    {
        return $authUser->can('Restore:ImportLog');
    }

    public function forceDelete(AuthUser $authUser, ImportLog $importLog): bool
    {
        return $authUser->can('ForceDelete:ImportLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ImportLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ImportLog');
    }

    public function replicate(AuthUser $authUser, ImportLog $importLog): bool
    {
        return $authUser->can('Replicate:ImportLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ImportLog');
    }

}