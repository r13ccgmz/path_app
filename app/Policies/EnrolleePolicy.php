<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Enrollee;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrolleePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Enrollee');
    }

    public function view(AuthUser $authUser, Enrollee $enrollee): bool
    {
        return $authUser->can('View:Enrollee');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Enrollee');
    }

    public function update(AuthUser $authUser, Enrollee $enrollee): bool
    {
        return $authUser->can('Update:Enrollee');
    }

    public function delete(AuthUser $authUser, Enrollee $enrollee): bool
    {
        return $authUser->can('Delete:Enrollee');
    }

    public function restore(AuthUser $authUser, Enrollee $enrollee): bool
    {
        return $authUser->can('Restore:Enrollee');
    }

    public function forceDelete(AuthUser $authUser, Enrollee $enrollee): bool
    {
        return $authUser->can('ForceDelete:Enrollee');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Enrollee');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Enrollee');
    }

    public function replicate(AuthUser $authUser, Enrollee $enrollee): bool
    {
        return $authUser->can('Replicate:Enrollee');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Enrollee');
    }

}